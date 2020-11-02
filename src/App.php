<?php

declare(strict_types=1);

namespace DiceRobot;

use DiceRobot\Data\{Config, Dice, Subexpression};
use DiceRobot\Enum\AppStatusEnum;
use DiceRobot\Exception\{MiraiApiException, RuntimeException};
use DiceRobot\Factory\LoggerFactory;
use DiceRobot\Service\{ApiService, ResourceService, RobotService, StatisticsService};
use DiceRobot\Handlers\HeartbeatHandler;
use DiceRobot\Handlers\ReportHandler;
use DiceRobot\Traits\AppTraits\StatusTrait;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Swoole\Timer;

/**
 * Class App
 *
 * DiceRobot application.
 *
 * @package DiceRobot
 */
class App
{
    /** @var ContainerInterface Container */
    protected ContainerInterface $container;

    /** @var Config Config */
    protected Config $config;

    /** @var ApiService API service */
    protected ApiService $api;

    /** @var ResourceService Data service */
    protected ResourceService $resource;

    /** @var RobotService Robot service */
    protected RobotService $robot;

    /** @var StatisticsService Statistics service */
    protected StatisticsService $statistics;

    /** @var LoggerInterface Logger */
    protected LoggerInterface $logger;

    /** @var HeartbeatHandler Heartbeat handler */
    protected HeartbeatHandler $heartbeatHandler;

    /** @var ReportHandler Report handler */
    protected ReportHandler $reportHandler;

    use StatusTrait;

    /**
     * The constructor.
     *
     * @param ContainerInterface $container
     * @param Config $config
     * @param ApiService $api
     * @param ResourceService $resource
     * @param RobotService $robot
     * @param StatisticsService $statistics
     * @param LoggerFactory $loggerFactory
     */
    public function __construct(
        ContainerInterface $container,
        Config $config,
        ApiService $api,
        ResourceService $resource,
        RobotService $robot,
        StatisticsService $statistics,
        LoggerFactory $loggerFactory
    ) {
        $this->status = AppStatusEnum::WAITING();
        $this->container = $container;
        $this->config = $config;
        $this->api = $api;
        $this->resource = $resource;
        $this->robot = $robot;
        $this->statistics = $statistics;
        $this->logger = $loggerFactory->create("Application");

        $this->logger->notice("Application started.");
    }

    /**
     * Initialize application.
     */
    public function initialize(): void
    {
        /** Primary initialization */
        try {
            $this->api->initialize($this->config);
            $this->resource->initialize($this->config);
            $this->robot->initialize($this->config);
            $this->statistics->initialize();
        } catch (RuntimeException $e) {
            $this->status = AppStatusEnum::STOPPED();

            $this->logger->emergency("Initialize application failed.");

            return;
        }

        $this->loadConfig();

        /** Secondary initialization */
        Dice::globalInitialize($this->config);
        Subexpression::globalInitialize($this->config);

        /** Set default handlers */
        $this->heartbeatHandler = $this->container->get(HeartbeatHandler::class);
        $this->reportHandler = $this->container->get(ReportHandler::class);

        $this->status = AppStatusEnum::HOLDING();

        $this->logger->notice("Application initialized.");
    }

    /**
     * Register routes.
     *
     * @param array $routes The routes
     */
    public function registerRoutes(array $routes): void
    {
        $this->reportHandler->registerRoutes($routes);

        $this->logger->info("Report routes registered.");
    }

    /**
     * Load config.
     */
    protected function loadConfig(): void
    {
        $reflectionClass = new ReflectionClass(Config::class);
        $property = $reflectionClass->getProperty("data");
        $property->setAccessible(true);

        // Set new config
        $property->setValue($this->config, (new Config($this->container, $this->resource->getConfig()))->all());

        // Reload logger factory
        $this->container->get(LoggerFactory::class)->reload($this->config);
    }

    /******************************************************************************
     *                              Application APIs                              *
     ******************************************************************************/

    /**
     * Get robot profile.
     *
     * @return array Result data
     */
    public function profile(): array
    {
        return [
            "id" => $this->robot->getId(),
            "nickname" => $this->robot->getNickname(),
            "friends" => $this->robot->getFriendsCount(),
            "groups" => $this->robot->getGroupsCount(),
            "startup" => DICEROBOT_STARTUP,
            "version" => DICEROBOT_VERSION
        ];
    }

    /**
     * Get application status.
     *
     * @return array Result data
     */
    public function status(): array
    {
        return [$this->getStatus()->getValue()];
    }

    /**
     * Get application statistics.
     *
     * @return array Result code and data
     */
    public function statistics(): array
    {
        $statistics = $this->statistics->getAllData();
        $data = [
            "sum" => $statistics["sum"],
            "orders" => [],
            "groups" => [],
            "friends" => [],
            "timeline" => $this->statistics->getTimeline(),
            "counts" => $this->statistics->getCounts()
        ];

        foreach (["orders", "groups", "friends"] as $type) {
            arsort($statistics[$type], SORT_NUMERIC);
            $statistics[$type] = array_slice($statistics[$type], 0, 5, true);
        }

        foreach ($statistics["orders"] as $order => $value) {
            $data["orders"][] = [$order, $value];
        }

        foreach ($statistics["groups"] as $id => $value) {
            $data["groups"][] = [$id, $this->robot->getGroup($id)->name ?? "[Unknown Group]", $value];
        }

        foreach ($statistics["friends"] as $id => $value) {
            $data["friends"][] = [$id, $this->robot->getFriend($id)->nickname ?? "[Unknown Friend]", $value];
        }

        return $data;
    }

    /**
     * Pause application.
     *
     * @return int Result code
     */
    public function pause(): int
    {
        if ($this->getStatus()->equals(AppStatusEnum::PAUSED())) {
            // Application is already paused
            return -1000;
        } elseif ($this->getStatus()->lessThan(AppStatusEnum::PAUSED())) {
            // Cannot be paused
            return -1001;
        }

        $this->setStatus(AppStatusEnum::PAUSED());

        return 0;
    }

    /**
     * Rerun application.
     *
     * @return int Result code
     */
    public function run(): int
    {
        if ($this->getStatus()->equals(AppStatusEnum::RUNNING())) {
            // Application is already running
            return -1010;
        } elseif (!$this->getStatus()->equals(AppStatusEnum::PAUSED())) {
            // Cannot be rerun
            return -1011;
        }

        try {
            // Initialize session, then update robot service
            if ($this->api->initSession($this->robot->getAuthKey(), $this->robot->getId()) && $this->robot->update()) {
                $this->setStatus(AppStatusEnum::RUNNING());

                $this->logger->notice("Application rerun.");

                return 0;
            } else {
                $this->logger->critical("Rerun application failed.");
            }
        } catch (MiraiApiException $e) {  // TODO: catch (MiraiApiException) in PHP 8
            $this->logger->alert("Rerun application failed, unable to call Mirai API.");
        }

        return -1012;
    }

    /**
     * Reload the config.
     *
     * @return int Result code
     */
    public function reload(): int
    {
        $this->loadConfig();

        $this->logger->notice("Application reloaded.");

        return 0;
    }

    /**
     * Stop application and release resources.
     *
     * @return int Result code
     */
    public function stop(): int
    {
        $this->setStatus(AppStatusEnum::STOPPED());

        // Stop, save and release
        Timer::clearAll();
        $this->resource->saveAll();
        saber_pool_release();

        $this->logger->notice("Application exited.");

        return 0;
    }

    /**
     * Handle heartbeat.
     */
    public function heartbeat(): void
    {
        $this->heartbeatHandler->handle();
    }

    /**
     * Handle report
     *
     * @param string $content Report content
     */
    public function report(string $content): void
    {
        $this->reportHandler->handle($content);
    }
}
