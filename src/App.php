<?php

declare(strict_types=1);

namespace DiceRobot;

use DiceRobot\Data\{Dice, Subexpression};
use DiceRobot\Enum\AppStatusEnum;
use DiceRobot\Exception\MiraiApiException;
use DiceRobot\Factory\LoggerFactory;
use DiceRobot\Service\{ApiService, ResourceService, RobotService, StatisticsService};
use DiceRobot\Handlers\HeartbeatHandler;
use DiceRobot\Handlers\ReportHandler;
use DiceRobot\Traits\AppTraits\StatusTrait;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Selective\Config\Configuration;
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

    /** @var Configuration Config */
    protected Configuration $config;

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
     * @param Configuration $config
     * @param ApiService $api
     * @param ResourceService $resource
     * @param RobotService $robot
     * @param StatisticsService $statistics
     * @param LoggerFactory $loggerFactory
     * @param HeartbeatHandler $heartbeatHandler
     * @param ReportHandler $reportHandler
     */
    public function __construct(
        ContainerInterface $container,
        Configuration $config,
        ApiService $api,
        ResourceService $resource,
        RobotService $robot,
        StatisticsService $statistics,
        LoggerFactory $loggerFactory,
        HeartbeatHandler $heartbeatHandler,
        ReportHandler $reportHandler
    ) {
        $this->status = AppStatusEnum::WAITING();
        $this->container = $container;
        $this->config = $config;
        $this->api = $api;
        $this->resource = $resource;
        $this->robot = $robot;
        $this->statistics = $statistics;
        $this->logger = $loggerFactory->create("Application");
        $this->heartbeatHandler = $heartbeatHandler;
        $this->reportHandler = $reportHandler;
        $this->logger->notice("Application started.");

        $this->initialize();
    }

    /**
     * Initialize application.
     */
    protected function initialize(): void
    {
        /** Primary initialization */
        if (!$this->resource->initialize() || !$this->statistics->initialize()) {
            $this->status = AppStatusEnum::STOPPED();

            $this->logger->emergency("Initialize application failed.");

            return;
        }

        /** Secondary initialization */
        $this->globalInitialize();  // Global initialization

        $this->status = AppStatusEnum::HOLDING();

        $this->logger->notice("Application initialized.");
    }

    /**
     * Initialize static variables.
     */
    protected function globalInitialize(): void
    {
        Dice::globalInitialize($this->config);
        Subexpression::globalInitialize($this->config);
    }

    /**
     * Register routes.
     *
     * @param array $routes The routes
     */
    public function registerRoutes(array $routes): void
    {
        $this->reportHandler->registerRoutes($routes);
    }

    /******************************************************************************
     *                              Application APIs                              *
     ******************************************************************************/

    /**
     * Get robot profile.
     *
     * @return array Result code and data
     */
    public function profile(): array
    {
        return [
            0,
            [
                "id" => $this->robot->getId(),
                "nickname" => $this->robot->getNickname(),
                "friends" => $this->robot->getFriendsCount(),
                "groups" => $this->robot->getGroupsCount(),
                "startup" => DICEROBOT_STARTUP,
                "version" => DICEROBOT_VERSION
            ]
        ];
    }

    /**
     * Get application status.
     *
     * @return array Result code and data
     */
    public function status(): int
    {
        return $this->getStatus()->getValue();
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

        return [0, $data];
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
            // Initialize API service, then update robot service
            if ($this->api->initialize($this->robot->getAuthKey(), $this->robot->getId()) && $this->robot->update()) {
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
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function reload(): int
    {
        /** @var Configuration $configuration */
        $configuration = $this->container->make(Configuration::class);

        $newConfig = $configuration->all();

        // Config should not be empty, or the custom settings is invalid
        if (!empty($newConfig)) {
            $reflectionClass = new ReflectionClass(Configuration::class);
            $property = $reflectionClass->getProperty("data");
            $property->setAccessible(true);

            // Set new config
            $property->setValue($this->config, $newConfig);

            // Initialize static variables
            $this->globalInitialize();

            $this->logger->notice("Application reloaded.");

            return 0;
        } else {
            $this->logger->notice("Reload application failed.");

            return -1020;
        }
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
