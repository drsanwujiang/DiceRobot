<?php

declare(strict_types=1);

namespace DiceRobot\Handlers;

use DiceRobot\App;
use DiceRobot\Action\{EventAction, MessageAction};
use DiceRobot\Data\Config;
use DiceRobot\Data\Report\{Event, InvalidReport, Message};
use DiceRobot\Data\Report\Message\{FriendMessage, GroupMessage, TempMessage};
use DiceRobot\Enum\AppStatusEnum;
use DiceRobot\Exception\{DiceRobotException, MiraiApiException};
use DiceRobot\Factory\{LoggerFactory, ReportFactory};
use DiceRobot\Interfaces\Report;
use DiceRobot\Service\{ApiService, RobotService, StatisticsService};
use DiceRobot\Traits\RouteCollectorTrait;
use DiceRobot\Util\Convertor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ReportHandler
 *
 * The report handler.
 *
 * @package DiceRobot\Handlers
 */
class ReportHandler
{
    /** @var ContainerInterface Container */
    protected ContainerInterface $container;

    /** @var Config Config */
    protected Config $config;

    /** @var App Application */
    protected App $app;

    /** @var ApiService API service */
    protected ApiService $api;

    /** @var RobotService Robot service */
    protected RobotService $robot;

    /** @var StatisticsService Statistics service */
    protected StatisticsService $statistics;

    /** @var LoggerInterface Logger */
    protected LoggerInterface $logger;

    use RouteCollectorTrait;

    /**
     * The constructor.
     *
     * @param ContainerInterface $container
     * @param Config $config
     * @param App $app
     * @param ApiService $api
     * @param RobotService $robot
     * @param StatisticsService $statistics
     * @param LoggerFactory $loggerFactory
     */
    public function __construct(
        ContainerInterface $container,
        Config $config,
        App $app,
        ApiService $api,
        RobotService $robot,
        StatisticsService $statistics,
        LoggerFactory $loggerFactory
    ) {
        $this->container = $container;
        $this->config = $config;
        $this->app = $app;
        $this->api = $api;
        $this->robot = $robot;
        $this->statistics = $statistics;
        $this->logger = $loggerFactory->create("Handler");
    }

    /**
     * Handle message and event report.
     *
     * @param string $reportContent
     */
    public function handle(string $reportContent): void
    {
        $this->logger->debug("Receive report, content: {$reportContent}");

        $this->logger->info("Report started.");

        // Validate
        if (!is_object($reportData = json_decode($reportContent))) {
            $this->logger->error("Report failed, JSON decode error.");

            return;
        } elseif (!$this->validate($report = ReportFactory::create($reportData))) {
            $this->logger->info("Report skipped, unsupported report.");

            return;
        }

        try {
            // Report
            if ($report instanceof Event) {
                $this->event($report);
            } elseif ($report instanceof Message) {
                $this->message($report);
            }
        } catch (MiraiApiException $e) {  // TODO: catch (MiraiApiException) in PHP 8
            $this->logger->alert("Report failed, unable to call Mirai API.");
        }
    }

    /**
     * @param Event $event
     *
     * @noinspection PhpRedundantCatchClauseInspection
     */
    protected function event(Event $event): void
    {
        // Check application status
        if ($this->app->getStatus()->lessThan(AppStatusEnum::RUNNING())) {
            $this->logger->info("Report skipped. Application status {$this->app->getStatus()}.");

            return;
        }

        if (empty($actionName = $this->matchEvent($event))) {
            $this->logger->info("Report skipped, matching miss.");

            return;
        }

        /** @var EventAction $action */
        $action = $this->container->make($actionName, [
            "event" => $event
        ]);

        try {
            $action();

            $this->logger->info("Report finished.");
        } catch (DiceRobotException $e) {  // Action interrupted, log error
            // TODO: $e::class, $action->event::class, $action::class in PHP 8
            $this->logger->error(
                "Report failed, " . get_class($e) . " occurred when handling " . get_class($action->event) .
                " and executing " . get_class($action)
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
    protected function message(Message $message): void
    {
        // Check application status
        if (!$this->app->getStatus()->equals(AppStatusEnum::RUNNING())) {
            $this->logger->info("Report skipped. Application status {$this->app->getStatus()}.");

            return;
        }

        if (!$message->parseMessageChain()) {
            $this->logger->error("Report failed, parse message error.");

            return;
        }

        list($filter, $at) = $this->filter($message);

        if (!$filter) {
            $this->logger->info("Report skipped, filter miss.");

            return;
        }

        list($match, $order, $actionName) = $this->matchMessage($message);

        if (empty($actionName)) {
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

        $this->statistics->addCount($match, get_class($message), $message->sender);

        if (!$action->checkActive()) {
            $this->logger->info("Report finished, robot inactive.");

            return;
        }

        try {
            $action();

            // Send reply if set
            if (!empty($action->reply)) {
                if ($action->message instanceof FriendMessage) {
                    $this->api->sendFriendMessage(
                        $action->message->sender->id,
                        Convertor::toMessageChain($action->reply)
                    );
                } elseif ($action->message instanceof GroupMessage) {
                    $this->api->sendGroupMessage(
                        $action->message->sender->group->id,
                        Convertor::toMessageChain($action->reply)
                    );
                } elseif ($action->message instanceof TempMessage) {
                    $this->api->sendTempMessage(
                        $action->message->sender->id,
                        $action->message->sender->group->id,
                        Convertor::toMessageChain($action->reply)
                    );
                }
            }

            $this->logger->info("Report finished.");
        } catch (DiceRobotException $e) {  // Action interrupted, send error message to group/user
            if ($action->message instanceof FriendMessage) {
                $this->api->sendFriendMessage(
                    $action->message->sender->id,
                    Convertor::toMessageChain($this->config->getString("errMsg.{$e}"))
                );
            } elseif ($action->message instanceof GroupMessage) {
                $this->api->sendGroupMessage(
                    $action->message->sender->group->id,
                    Convertor::toMessageChain($this->config->getString("errMsg.{$e}"))
                );
            } elseif ($action->message instanceof TempMessage) {
                $this->api->sendTempMessage(
                    $action->message->sender->id,
                    $action->message->sender->group->id,
                    Convertor::toMessageChain($this->config->getString("errMsg.{$e}"))
                );
            }

            // TODO: $e::class, $action->message::class, $action::class in PHP 8
            $this->logger->info(
                "Report finished, " . get_class($e) . " occurred when handling " .
                get_class($action->message) . " and executing " . get_class($action)
            );

            if (!empty($e->extraMessage)) {
                $this->logger->error("Extra message: {$e->extraMessage}.");
            }
        }
    }

    /**
     * Validate the report.
     *
     * @param Report $report
     *
     * @return bool
     */
    protected function validate(Report $report): bool
    {
        return !($report instanceof InvalidReport);
    }

    /**
     * Filter the order.
     *
     * @param Message $message
     *
     * @return array Filter and at
     */
    protected function filter(Message $message): array
    {
        if (preg_match(
            "/^(?:\[mirai:at:([1-9][0-9]*),.*?])?\s*[.。]\s*([\S\s]+)/",
            (string) $message,
            $matches
        )) {
            $message->message = "." . trim($matches[2]);

            $targetId = $matches[1];
            $at = $targetId == (string) $this->robot->getId();

            // At others
            if (!empty($targetId) && !$at) {
                return [false, null];
            }

            return [true, $at];
        }

        return [false, null];
    }
}