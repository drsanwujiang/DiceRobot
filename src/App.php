<?php

declare(strict_types=1);

namespace DiceRobot;

use Co;
use DiceRobot\Action\EventAction;
use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Dice;
use DiceRobot\Data\Subexpression;
use DiceRobot\Data\Report\{Event, InvalidReport, Message};
use DiceRobot\Data\Report\Message\{FriendMessage, GroupMessage, TempMessage};
use DiceRobot\Enum\AppStatusEnum;
use DiceRobot\Exception\{DiceRobotException, MiraiApiException};
use DiceRobot\Factory\{LoggerFactory, ReportFactory};
use DiceRobot\Interfaces\Report;
use DiceRobot\Service\{ApiService, ResourceService, RobotService};
use DiceRobot\Traits\RouteCollectorTrait;
use DiceRobot\Util\Convertor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;

/**
 * Class App
 *
 * DiceRobot application.
 *
 * @package DiceRobot
 */
class App
{
    /** @var AppStatusEnum Current status */
    private AppStatusEnum $status;

    /** @var ContainerInterface Container */
    private ContainerInterface $container;

    /** @var Configuration Config */
    private Configuration $config;

    /** @var ApiService API service */
    private ApiService $api;

    /** @var ResourceService Data service */
    private ResourceService $resource;

    /** @var RobotService Robot service */
    private RobotService $robot;

    /** @var LoggerInterface Logger */
    private LoggerInterface $logger;

    use RouteCollectorTrait;

    /**
     * The constructor.
     *
     * @param ContainerInterface $container
     * @param Configuration $config
     * @param ApiService $api
     * @param ResourceService $resource
     * @param RobotService $robot
     * @param LoggerFactory $loggerFactory
     */
    public function __construct(
        ContainerInterface $container,
        Configuration $config,
        ApiService $api,
        ResourceService $resource,
        RobotService $robot,
        LoggerFactory $loggerFactory
    ) {
        $this->status = AppStatusEnum::WAITING();
        $this->container = $container;
        $this->config = $config;
        $this->api = $api;
        $this->resource = $resource;
        $this->robot = $robot;
        $this->logger = $loggerFactory->create("Application");

        $this->logger->notice("Application started.");

        $this->initialize();
    }

    /**
     * Get status of the application.
     *
     * @return AppStatusEnum The status
     */
    public function getStatus(): AppStatusEnum
    {
        return $this->status;
    }

    /**
     * @param AppStatusEnum $status The status
     */
    public function setStatus(AppStatusEnum $status): void
    {
        $this->status = $status;

        if ($status->equals(AppStatusEnum::STOPPED()))
            $this->logger->notice("Application stopped.");
        elseif ($status->equals(AppStatusEnum::RUNNING()))
            $this->logger->notice("Application running.");
        elseif ($status->equals(AppStatusEnum::HOLDING()))
            $this->logger->warning("Application holding.");
    }

    /**
     * Initialize application.
     */
    private function initialize(): void
    {
        /** Global initialize */
        Dice::globalInitialize($this->config);
        Subexpression::globalInitialize($this->config);

        /** Initialize */
        $result = $this->resource->initialize();

        if ($result)
        {
            $this->status = AppStatusEnum::HOLDING();

            $this->logger->notice("Application initialized.");
        }
        else
        {
            $this->status = AppStatusEnum::STOPPED();

            $this->logger->emergency("Initialize application failed.");
        }
    }

    /******************************************************************************
     *                                 Server APIs                                *
     ******************************************************************************/

    /**
     * Handle heartbeat.
     */
    public function heartbeat(): void
    {
        $this->logger->debug("Heartbeat started. Application status {$this->status}.");

        /** Validate */
        if (!$this->getStatus()->equals(AppStatusEnum::RUNNING()))
        {
            $this->logger->info("Heartbeat skipped. Application status {$this->status}.");

            return;
        }

        /** Heartbeat */
        if ($this->resource->saveAll() && $this->checkSession() && $this->updateRobot())
        {
            $this->logger->info("Heartbeat finished. Application status {$this->status}.");
        }
        else
        {
            if ($this->status->equals(AppStatusEnum::RUNNING()))
                $this->setStatus(AppStatusEnum::HOLDING());

            $this->logger->alert("Heartbeat failed. Application status {$this->status}.");
        }
    }

    /**
     * Handle message and event report.
     *
     * @param string $reportContent
     */
    public function report(string $reportContent): void
    {
        $this->logger->info("Report started, content: {$reportContent}. Application status {$this->status}.");

        /** Validate */
        if (!is_object($reportData = json_decode($reportContent)))
        {
            $this->logger->error("Report failed, JSON decode error.");

            return;
        }
        elseif (!$this->validate($report = ReportFactory::create($reportData)))
        {
            $this->logger->info("Report skipped, unsupported report.");

            return;
        }

        try
        {
            /** Report */
            if ($report instanceof Event)
                $this->handleEvent($report);
            elseif ($report instanceof Message)
                $this->handleMessage($report);
        }
        // Call Mirai APIs failed
        catch (MiraiApiException $e)  // TODO: catch (MiraiApiException) in PHP 8
        {
            $this->logger->alert("Report failed, unable to call Mirai API.");
        }
    }

    /**
     * Stop application and release resources.
     */
    public function stop(): void
    {
        $this->setStatus(AppStatusEnum::STOPPED());
        $this->resource->saveAll();
        saber_pool_release();

        $this->logger->notice("Application exits.");
    }

    /******************************************************************************
     *                             Heartbeat handling                             *
     ******************************************************************************/

    /**
     * Check Mirai session status and extend its effective time.
     *
     * @return bool
     */
    public function checkSession(): bool
    {
        try
        {
            // Retry 3 times, for the case that heartbeat happens between BotOfflineEventDropped and BotReloginEvent
            for ($i = 0; $i < 3; $i++)
            {
                if (0 == $code = $this->api->verifySession($this->robot->getId())->getInt("code", -1))
                {
                    $this->logger->info("Session verified.");

                    return true;
                }
                else
                {
                    $this->logger->warning("Session unauthorized, code {$code}. Retry.");

                    Co::sleep(1);
                }
            }

            $this->logger->error("Session unauthorized, failed to retry. Try to initialize.");

            // Try to initialize API service
            if ($this->api->initialize($this->robot->getAuthKey(), $this->robot->getId()))
            {
                $this->logger->info("Session verified.");

                return true;
            }
            else
            {
                $this->logger->critical("Check session failed.");
            }
        }
        // Call Mirai APIs failed
        catch (MiraiApiException $e)  // TODO: catch (MiraiApiException) in PHP 8
        {
            $this->logger->alert("Check session failed, unable to call Mirai API.");
        }

        return false;
    }

    /**
     * Update robot service.
     *
     * @return bool
     */
    public function updateRobot(): bool
    {
        try
        {
            // TODO: Update robot's profile
            // Refresh friend list
            $this->robot->updateFriends($this->api->getFriendList()->all());
            // Refresh group list
            $this->robot->updateGroups($this->api->getGroupList()->all());
            // Report to DiceRobot API
            $this->api->updateRobotAsync($this->robot->getId());

            $this->logger->info("Robot updated.");

            return true;
        }
        // Call Mirai APIs failed
        catch (MiraiApiException $e)  // TODO: catch (MiraiApiException) in PHP 8
        {
            $this->logger->alert("Update robot failed, unable to call Mirai API.");
        }

        return false;
    }

    /******************************************************************************
     *                               Report handling                              *
     ******************************************************************************/

    /**
     * Validate the report.
     *
     * @param Report $report
     *
     * @return bool
     */
    private function validate(Report $report): bool
    {
        return !($report instanceof InvalidReport);
    }

    /**
     * @param Event $event
     *
     * @noinspection PhpRedundantCatchClauseInspection
     */
    private function handleEvent(Event $event): void
    {
        // Check application status
        if ($this->getStatus()->equals(AppStatusEnum::STOPPED()))
        {
            $this->logger->info("Report skipped. Application status {$this->status}.");

            return;
        }

        if (empty($actionName = $this->matchEvent($event)))
        {
            $this->logger->info("Report skipped, matching miss.");

            return;
        }

        /** @var EventAction $action */
        $action = $this->container->make($actionName, [
            "event" => $event
        ]);

        try
        {
            $action();

            $this->logger->info("Report finished.");
        }
        // Action interrupted, log error
        catch (DiceRobotException $e)
        {
            // TODO: $e::class, $action->event::class, $action::class in PHP 8
            $this->logger->error(
                "Report failed, " .
                get_class($e) .
                " occurred when handling " .
                get_class($action->event) .
                " and executing " .
                get_class($action)
            );
        }
    }

    /**
     * @param Message $message
     *
     * @throws MiraiApiException
     *
     * @noinspection PhpRedundantCatchClauseInspection
     */
    private function handleMessage(Message $message): void
    {
        // Check application status
        if (!$this->getStatus()->equals(AppStatusEnum::RUNNING()))
        {
            $this->logger->info("Report skipped. Application status {$this->status}.");

            return;
        }

        if (!$message->parseMessageChain())
        {
            $this->logger->error("Report failed, parse message error.");

            return;
        }

        list($filter, $at) = $this->filter($message);

        if (!$filter)
        {
            $this->logger->info("Report skipped, filter miss.");

            return;
        }

        list($match, $order, $actionName) = $this->matchMessage($message);

        if (empty($actionName))
        {
            $this->logger->info("Report skipped, matching miss.");

            return;
        }

        /** @var MessageAction $action */
        $action = $this->container->make($actionName, [
            "message" => $message,
            "match" => $match,
            "order" => $order,
            "at" => $at
        ]);

        if (!$action->checkActive())
        {
            $this->logger->info("Report finished, robot inactive.");

            return;
        }

        try
        {
            $action();

            // Send reply if set
            if (!empty($action->reply))
            {
                if ($action->message instanceof FriendMessage)
                    $this->api->sendFriendMessage(
                        $action->message->sender->id,
                        Convertor::toMessageChain($action->reply)
                    );
                elseif ($action->message instanceof GroupMessage)
                    $this->api->sendGroupMessage(
                        $action->message->sender->group->id,
                        Convertor::toMessageChain($action->reply)
                    );
                elseif ($action->message instanceof TempMessage)
                    $this->api->sendTempMessage(
                        $action->message->sender->id,
                        $action->message->sender->group->id,
                        Convertor::toMessageChain($action->reply)
                    );
            }

            $this->logger->info("Report finished.");
        }
        // Action interrupted, send error message to group/user
        catch (DiceRobotException $e)
        {
            if ($action->message instanceof FriendMessage)
                $this->api->sendFriendMessage(
                    $action->message->sender->id,
                    Convertor::toMessageChain($this->config->getString("errorMessage.{$e}"))
                );
            elseif ($action->message instanceof GroupMessage)
                $this->api->sendGroupMessage(
                    $action->message->sender->group->id,
                    Convertor::toMessageChain($this->config->getString("errorMessage.{$e}"))
                );
            elseif ($action->message instanceof TempMessage)
                $this->api->sendTempMessage(
                    $action->message->sender->id,
                    $action->message->sender->group->id,
                    Convertor::toMessageChain($this->config->getString("errorMessage.{$e}"))
                );

            // TODO: $e::class, $action->message::class, $action::class in PHP 8
            $this->logger->info(
                "Report finished, " . get_class($e) . " occurred when handling " .
                get_class($action->message) . " and executing " . get_class($action)
            );

            if (!empty($e->extraMessage))
                $this->logger->error("Extra message: {$e->extraMessage}.");
        }
    }

    /**
     * Filter the order.
     *
     * @param Message $message
     *
     * @return array Filter and at
     */
    private function filter(Message $message): array
    {
        if (preg_match("/^(?:\[mirai:at:([1-9][0-9]*),.*?])?\s*[.。]\s*([\S\s]+)/", (string) $message, $matches))
        {
            $message->message = "." . trim($matches[2]);

            $targetId = $matches[1];
            $at = $targetId == (string) $this->robot->getId();

            // At others
            if (!empty($targetId) && !$at)
                return [false, NULL];

            return [true, $at];
        }

        return [false, NULL];
    }
}
