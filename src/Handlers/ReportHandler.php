<?php

declare(strict_types=1);

namespace DiceRobot\Handlers;

use DiceRobot\AppStatus;
use DiceRobot\Action\{EventAction, MessageAction};
use DiceRobot\Data\Config;
use DiceRobot\Data\Report\{Event, InvalidReport, Message};
use DiceRobot\Data\Report\Message\{OtherClientMessage, StrangerMessage};
use DiceRobot\Enum\AppStatusEnum;
use DiceRobot\Exception\{DiceRobotException, MiraiApiException};
use DiceRobot\Factory\{LoggerFactory, ReportFactory};
use DiceRobot\Interfaces\Report;
use DiceRobot\Service\{ApiService, RobotService, StatisticsService};
use DiceRobot\Traits\RouteCollectorTrait;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class ReportHandler
 *
 * Report handler.
 *
 * @package DiceRobot\Handlers
 */
class ReportHandler
{
    /** @var ContainerInterface Container. */
    protected ContainerInterface $container;

    /** @var Config DiceRobot config. */
    protected Config $config;

    /** @var ApiService API service. */
    protected ApiService $api;

    /** @var RobotService Robot service. */
    protected RobotService $robot;

    /** @var StatisticsService Statistics service. */
    protected StatisticsService $statistics;

    /** @var TrpgLogHandler TRPG log handler. */
    protected TrpgLogHandler $log;

    /** @var LoggerInterface Logger. */
    protected LoggerInterface $logger;

    use RouteCollectorTrait;

    /**
     * The constructor.
     *
     * @param ContainerInterface $container Container.
     * @param Config $config DiceRobot config.
     * @param ApiService $api API service.
     * @param RobotService $robot Robot service.
     * @param StatisticsService $statistics Statistics service.
     * @param TrpgLogHandler $log TRPG log handler.
     * @param LoggerFactory $loggerFactory Logger factory.
     */
    public function __construct(
        ContainerInterface $container,
        Config $config,
        ApiService $api,
        RobotService $robot,
        StatisticsService $statistics,
        TrpgLogHandler $log,
        LoggerFactory $loggerFactory
    ) {
        $this->container = $container;
        $this->config = $config;
        $this->api = $api;
        $this->robot = $robot;
        $this->statistics = $statistics;
        $this->log = $log;

        $this->logger = $loggerFactory->create("Report");

        $this->logger->debug("Report handler created.");
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
        $this->logger->debug("Report handler destructed.");
    }

    /**
     * Handle message and event report.
     *
     * @param string $content Report content.
     *
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function handle(string $content): void
    {
        $this->logger->debug("Report received, content: {$content}");

        $this->logger->info("Report started.");

        // Validate
        if (!is_object($data = json_decode($content))) {
            $this->logger->error("Report failed, JSON decode error.");

            return;
        } elseif (!$this->validate($report = ReportFactory::create($data))) {
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
        } catch (Throwable $t) {
            $details = sprintf(
                "Type: %s\nCode: %s\nMessage: %s\nFile: %s\nLine: %s\nTrace: %s",
                get_class($t),
                $t->getCode(),
                $t->getMessage(),
                $t->getFile(),
                $t->getLine(),
                $t->getTraceAsString()
            );

            $this->logger->error("Report failed, unexpected exception occurred:\n{$details}.");
        }
    }

    /**
     * @param Event $event Event.
     *
     * @noinspection PhpRedundantCatchClauseInspection
     * @noinspection PhpDocMissingThrowsInspection
     */
    protected function event(Event $event): void
    {
        // Check application status
        if (($status = AppStatus::getStatus())->lessThan(AppStatusEnum::RUNNING())) {
            $this->logger->info("Report skipped. Application status {$status}.");

            return;
        }

        // Check matching
        if (empty($actionName = $this->matchEvent($event))) {
            $this->logger->info("Report skipped, matching miss.");

            return;
        }

        /** @var EventAction $action */
        $action = $this->container->make($actionName, [
            "event" => $event
        ]);

        try {
            $action();  // Invoke action

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
     * @param Message $message Message.
     *
     * @noinspection PhpRedundantCatchClauseInspection
     * @noinspection PhpDocMissingThrowsInspection
     * @noinspection PhpUnhandledExceptionInspection
     */
    protected function message(Message $message): void
    {
        // Check application status
        if (!($status = AppStatus::getStatus())->equals(AppStatusEnum::RUNNING())) {
            $this->logger->info("Report skipped. Application status {$status}.");

            return;
        }

        // Check message type
        if ($message instanceof OtherClientMessage || $message instanceof StrangerMessage) {
            $this->logger->info("Report skipped, message type unacceptable.");

            return;
        }

        // Check message chain
        if (!$message->parseMessageChain()) {
            $this->logger->info("Report skipped, message not parsable.");

            return;
        }

        $this->log->handle($message);

        list($filter, $at) = $this->filter($message);

        // Check filter
        if (!$filter) {
            $this->logger->info("Report skipped, filter miss.");

            return;
        }

        list($match, $order, $actionName) = $this->matchMessage($message);

        // Check matching
        if (empty($actionName)) {
            $this->logger->info("Report skipped, matching miss.");

            return;
        }

        /** @var MessageAction $action */
        $action = $this->container->make($actionName, [
            "message" => $message,
            "match" => strtolower($match),
            "order" => $order,
            "at" => $at
        ]);

        $action->initialize();  // Initialize action

        // Check active
        if (!$action->checkActive()) {
            $this->logger->info("Report finished, robot inactive.");

            return;
        }

        $this->statistics->addCount($match, get_class($message), $message->sender);  // Update statistics

        try {
            $action();  // Invoke action
            $action->sendReplies();  // Send replies if necessary

            $this->log->handle($message, $action);  // Update log if enabled

            $this->logger->info("Report finished.");
        } catch (DiceRobotException $e) {
            // Action interrupted, send error message to group/user
            $action->sendMessage($this->config->getErrMsg((string) $e));

            $this->log->handle($message, $action, $e);  // Update log if enabled

            // TODO: $e::class, $action->message::class, $action::class in PHP 8
            $this->logger->info(
                "Report finished, " . get_class($e) . " occurred when handling " .
                get_class($action->getMessage()) . " and executing " . get_class($action) . "."
            );

            if (!empty($e->extraMessage)) {
                $this->logger->error("Report finished with extra message: {$e->extraMessage}.");
            }
        }
    }

    /**
     * Validate the report.
     *
     * @param Report $report Report.
     *
     * @return bool Validity.
     */
    protected function validate(Report $report): bool
    {
        return !($report instanceof InvalidReport);
    }

    /**
     * Filter the order.
     *
     * @param Message $message Message.
     *
     * @return array Filter and at.
     */
    protected function filter(Message $message): array
    {
        if (preg_match(
            "/^(?:\[mirai:at:([1-9][0-9]*),.*?])?\s*[.\x{3002}]\s*([\S\s]+)$/u",
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