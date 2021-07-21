<?php

declare(strict_types=1);

namespace DiceRobot;

use DiceRobot\Data\{Config, Dice, Subexpression};
use DiceRobot\Enum\AppStatusEnum;
use DiceRobot\Exception\RuntimeException;
use DiceRobot\Factory\LoggerFactory;
use DiceRobot\Handlers\ReportHandler;
use DiceRobot\Service\{ApiService, HeartbeatService, ResourceService, RobotService, StatisticsService};
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
    /** @var ContainerInterface Container. */
    protected ContainerInterface $container;

    /** @var Config DiceRobot config. */
    protected Config $config;

    /** @var ApiService API service. */
    protected ApiService $api;

    /** @var HeartbeatService Heartbeat service. */
    protected HeartbeatService $heartbeat;

    /** @var ResourceService Resource service. */
    protected ResourceService $resource;

    /** @var RobotService Robot service. */
    protected RobotService $robot;

    /** @var StatisticsService Statistics service. */
    protected StatisticsService $statistics;

    /** @var ReportHandler Report handler. */
    protected ReportHandler $reportHandler;

    /** @var LoggerInterface Logger. */
    protected LoggerInterface $logger;

    /**
     * The constructor.
     *
     * @param ContainerInterface $container Container.
     * @param Config $config DiceRobot config.
     * @param ApiService $api API service.
     * @param HeartbeatService $heartbeat Heartbeat service.
     * @param ResourceService $resource Resource service.
     * @param RobotService $robot Robot service.
     * @param StatisticsService $statistics Statistics service.
     * @param ReportHandler $reportHandler Report handler.
     * @param LoggerFactory $loggerFactory Logger factory.
     */
    public function __construct(
        ContainerInterface $container,
        Config $config,
        ApiService $api,
        HeartbeatService $heartbeat,
        ResourceService $resource,
        RobotService $robot,
        StatisticsService $statistics,
        ReportHandler $reportHandler,
        LoggerFactory $loggerFactory
    ) {
        $this->container = $container;
        $this->config = $config;
        $this->api = $api;
        $this->heartbeat = $heartbeat;
        $this->resource = $resource;
        $this->robot = $robot;
        $this->statistics = $statistics;
        $this->reportHandler = $reportHandler;

        $this->logger = $loggerFactory->create("Application");

        $this->logger->notice("Application started.");
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
        $this->logger->notice("Application exited.");
    }

    /**
     * Initialize application.
     */
    public function initialize(): void
    {
        /** Services initialization */
        try {
            $this->api->initialize($this->config);
            $this->heartbeat->initialize();
            $this->resource->initialize($this->config);
            $this->robot->initialize();
            $this->statistics->initialize();
        } catch (RuntimeException $e) {
            $this->logger->emergency("Initialize application failed.");

            $this->stop();

            return;
        }

        $this->loadConfig();

        /** Utils initialization */
        Dice::initialize($this->config);
        Subexpression::initialize($this->config);
        AppStatus::initialize($this->container->get(LoggerFactory::class));

        $this->logger->notice("Application initialized.");
    }

    /**
     * Register routes.
     *
     * @param array $routes Routes.
     */
    public function registerRoutes(array $routes): void
    {
        $this->reportHandler->registerRoutes($routes);

        $this->logger->info("Report routes registered.");
    }

    /**
     * Load config.
     *
     * @noinspection PhpUndefinedMethodInspection
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

    /**
     * Set panel config.
     *
     * @param array $data Panel config data.
     *
     * @return bool Success.
     */
    public function setConfig(array $data): bool
    {
        if (!$this->resource->getConfig()->setConfig($data)) {
            return false;
        }

        $this->loadConfig();

        return true;
    }

    /**
     * Get application status.
     *
     * @return AppStatusEnum Application status.
     */
    public function getStatus(): AppStatusEnum
    {
        return AppStatus::getStatus();
    }

    /**
     * Handle report.
     *
     * @param string $content Report content
     */
    public function report(string $content): void
    {
        $this->reportHandler->handle($content);
    }

    /**
     * Pause application.
     *
     * @return int Result code.
     */
    public function pause(): int
    {
        if (AppStatus::getStatus()->equals(AppStatusEnum::PAUSED())) {
            // Application is already paused
            return -1;
        } elseif (AppStatus::getStatus()->lessThan(AppStatusEnum::PAUSED())) {
            // Cannot be paused
            return -2;
        }

        AppStatus::pause();

        return 0;
    }

    /**
     * Rerun application.
     *
     * @return int Result code.
     */
    public function run(): int
    {
        if (AppStatus::getStatus()->equals(AppStatusEnum::RUNNING())) {
            // Application is already running
            return -1;
        } elseif (AppStatus::getStatus()->equals(AppStatusEnum::PAUSED())) {
            // Cannot be rerun
            return -2;
        } elseif (AppStatus::getStatus()->equals(AppStatusEnum::HOLDING())) {
            // Enable heartbeat
            if ($this->heartbeat->enable()) {
                $this->logger->notice("Application rerun.");

                return 0;
            } else {
                $this->logger->critical("Rerun application failed.");

                return -3;
            }
        } else {
            // Cannot be rerun
            return -2;
        }
    }

    /**
     * Reload the resources.
     *
     * @return int Result code.
     */
    public function reload(): int
    {
        AppStatus::pause();

        if (!$this->resource->reload()) {
            $this->logger->critical("Reload application failed.");

            return -1;
        }

        $this->loadConfig();
        AppStatus::run();

        $this->logger->notice("Application reloaded.");

        return 0;
    }

    /**
     * Stop application and release resources.
     *
     * @return int Result code.
     */
    public function stop(): int
    {
        AppStatus::stop();

        // Stop, save and release
        Timer::clearAll();
        saber_pool_release();

        if ($this->resource->save()) {
            return 0;
        } else {
            $this->logger->alert("Application cannot normally exit. Resources and data may not be saved.");

            return -1;
        }
    }
}
