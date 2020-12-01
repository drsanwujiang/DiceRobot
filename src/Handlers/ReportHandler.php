<?php

declare(strict_types=1);

namespace DiceRobot\Handlers;

use DiceRobot\Action\{EventAction, MessageAction};
use DiceRobot\App;
use DiceRobot\Data\Config;
use DiceRobot\Data\Report\{Event, InvalidReport, Message};
use DiceRobot\Enum\AppStatusEnum;
use DiceRobot\Exception\{DiceRobotException, MiraiApiException};
use DiceRobot\Factory\{LoggerFactory, ReportFactory};
use DiceRobot\Interfaces\Report;
use DiceRobot\Service\{ApiService, RobotService, StatisticsService};
use DiceRobot\Traits\RouteCollectorTrait;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

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

    /** @var App Application. */
    protected App $app;

    /** @var ApiService API service. */
    protected ApiService $api;

    /** @var RobotService Robot service. */
    protected RobotService $robot;

    /** @var StatisticsService Statistics service. */
    protected StatisticsService $statistics;

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
     * @param LoggerFactory $loggerFactory Logger factory.
     */
    public function __construct(
        ContainerInterface $container,
        Config $config,
        ApiService $api,
        RobotService $robot,
        StatisticsService $statistics,
        LoggerFactory $loggerFactory
    ) {
        $this->container = $container;
        $this->config = $config;
        $this->api = $api;
        $this->robot = $robot;
        $this->statistics = $statistics;

        $this->logger = $loggerFactory->create("Handler");

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
     * Initialize report handler.
     *
     * @param App $app Application.
     */
    public function initialize(App $app): void
    {
        $this->app = $app;

        $this->logger->info("Report handler initialized.");
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
        $this->logger->debug("Receive report, content: {$content}");

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
        }
    }

    /**
     * @param Event $event Event.
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
     * @param Message $message Message.
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
            "match" => strtolower($match),
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

            $action->sendReplies();

            $this->logger->info("Report finished.");
        } catch (DiceRobotException $e) {
            // Action interrupted, send error message to group/user
            $action->sendMessage($this->config->getErrMsg((string) $e));

            // TODO: $e::class, $action->message::class, $action::class in PHP 8
            $this->logger->info(
                "Report finished, " . get_class($e) . " occurred when handling " .
                get_class($action->message) . " and executing " . get_class($action) . "."
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