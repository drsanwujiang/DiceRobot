<?php

declare(strict_types=1);

namespace DiceRobot;

use DiceRobot\Data\{Config, CustomConfig, Dice, Subexpression};
use DiceRobot\Enum\AppStatusEnum;
use DiceRobot\Exception\RuntimeException;
use DiceRobot\Factory\LoggerFactory;
use DiceRobot\Handlers\ReportHandler;
use DiceRobot\Service\{ApiService, HeartbeatService, LogService, MqService, ResourceService, RobotService,
    StatisticsService};
use DiceRobot\Util\Environment;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Swoole\Coroutine\System;
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

    /** @var MqService Message queue service. */
    protected MqService $mq;

    /** @var LogService Log service. */
    protected LogService $log;

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

    /** @var bool Enabling flag. */
    protected bool $enabling = false;

    /**
     * The constructor.
     *
     * @param ContainerInterface $container Container.
     * @param Config $config DiceRobot config.
     * @param ApiService $api API service.
     * @param HeartbeatService $heartbeat Heartbeat service.
     * @param MqService $mq Message queue service.
     * @param LogService $log Log service.
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
        MqService $mq,
        LogService $log,
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
        $this->mq = $mq;
        $this->log = $log;
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
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function initialize(): void
    {
        // Initialize services
        try {
            $this->api->initialize();
            $this->log->initialize();
            $this->resource->initialize($this->config);
            $this->robot->initialize();
            $this->statistics->initialize();
            $this->heartbeat->initialize($this);
            $this->mq->initialize();
        } catch (RuntimeException $e) {
            $this->logger->emergency("Failed to initialize application.");

            $this->stop();

            return;
        }

        // Load panel config
        $this->config->load($this->container->make(CustomConfig::class), $this->resource->getConfig());

        // Initialize utils
        Environment::initialize();
        Dice::initialize($this->config);
        Subexpression::initialize($this->config);
        AppStatus::initialize($this->container->get(LoggerFactory::class));

        // Auto enable timer
        Timer::after(10000, function () {
            $this->logger->notice("Try to enable application.");

            if ($this->enable(false) < 0) {
                $this->logger->warning("Auto enable application failed, wait for Mirai event.");
            }
        });

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
     * Enable application.
     *
     * @param bool $logError Whether error should be logged.
     *
     * @return int Result.
     */
    public function enable(bool $logError = true): int
    {
        if ($this->enabling) {
            return 1;
        }

        $this->enabling = true;

        if ($this->heartbeat->enable()) {
            // After heartbeat enabled, application should be enabled whether MQ service is enabled
            go(function () {
                System::sleep(3);  // Wait for report
                $this->mq->enable();
            });

            AppStatus::run();
            $this->enabling = false;

            $this->logger->notice("Application enabled.");

            return 0;
        } else {
            $this->disable($logError);
            $this->enabling = false;

            if ($logError) {
                $this->logger->critical("Failed to enable application.");
            }

            return -1;
        }
    }

    /**
     * Disable application.
     *
     * @param bool $logError Whether error should be logged.
     */
    public function disable(bool $logError = true): void
    {
        $this->heartbeat->disable($logError);
        $this->mq->disable($logError);
        AppStatus::hold();

        if ($logError) {
            $this->logger->warning("Application disabled.");
        }
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
        }

        if (AppStatus::getStatus()->equals(AppStatusEnum::PAUSED())) {
            AppStatus::run();

            return 0;
        } else {
            // Cannot be rerun
            return -2;
        }
    }

    /**
     * Reload the resources.
     *
     * @return int Result code.
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function reload(): int
    {
        AppStatus::pause();

        if (!$this->resource->reload()) {
            $this->logger->critical("Reload application failed.");

            return -1;
        }

        $this->config->load($this->container->make(CustomConfig::class), $this->resource->getConfig());

        // Reload logger factory
        /** @var LoggerFactory $loggerFactory */
        $loggerFactory = $this->container->get(LoggerFactory::class);
        $loggerFactory->reload();

        // Utils reinitialization
        Dice::initialize($this->config);
        Subexpression::initialize($this->config);

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

        // Disable stateful services
        $this->heartbeat->disable(false);
        $this->mq->disable(false);

        // Clear all timers, release HTTP clients
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
