<?php

declare(strict_types=1);

namespace DiceRobot;

use DiceRobot\Data\{Config, Dice, Subexpression};
use DiceRobot\Enum\AppStatusEnum;
use DiceRobot\Exception\{MiraiApiException, RuntimeException};
use DiceRobot\Factory\LoggerFactory;
use DiceRobot\Handlers\{HeartbeatHandler, ReportHandler};
use DiceRobot\Service\{ApiService, ResourceService, RobotService, StatisticsService};
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
    /** @var ContainerInterface Container. */
    protected ContainerInterface $container;

    /** @var Config DiceRobot config. */
    protected Config $config;

    /** @var ApiService API service. */
    protected ApiService $api;

    /** @var ResourceService Resource service. */
    protected ResourceService $resource;

    /** @var RobotService Robot service. */
    protected RobotService $robot;

    /** @var StatisticsService Statistics service. */
    protected StatisticsService $statistics;

    /** @var HeartbeatHandler Heartbeat handler. */
    protected HeartbeatHandler $heartbeatHandler;

    /** @var ReportHandler Report handler. */
    protected ReportHandler $reportHandler;

    /** @var LoggerInterface Logger. */
    protected LoggerInterface $logger;

    use StatusTrait;

    /**
     * The constructor.
     *
     * @param ContainerInterface $container Container.
     * @param Config $config DiceRobot config.
     * @param ApiService $api API service.
     * @param ResourceService $resource Resource service.
     * @param RobotService $robot Robot service.
     * @param StatisticsService $statistics Statistics service.
     * @param HeartbeatHandler $heartbeatHandler Heartbeat handler.
     * @param ReportHandler $reportHandler Report handler.
     * @param LoggerFactory $loggerFactory Logger factory.
     */
    public function __construct(
        ContainerInterface $container,
        Config $config,
        ApiService $api,
        ResourceService $resource,
        RobotService $robot,
        StatisticsService $statistics,
        HeartbeatHandler $heartbeatHandler,
        ReportHandler $reportHandler,
        LoggerFactory $loggerFactory
    ) {
        $this->status = AppStatusEnum::WAITING();
        $this->container = $container;
        $this->config = $config;
        $this->api = $api;
        $this->resource = $resource;
        $this->robot = $robot;
        $this->statistics = $statistics;
        $this->heartbeatHandler = $heartbeatHandler;
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
        /** Primary initialization */
        try {
            $this->api->initialize($this->config);
            $this->resource->initialize($this->config);
            $this->robot->initialize($this->config);
            $this->statistics->initialize();
        } catch (RuntimeException $e) {
            $this->logger->emergency("Initialize application failed.");

            $this->stop();

            return;
        }

        $this->loadConfig();

        /** Secondary initialization */
        $this->heartbeatHandler->initialize($this);
        $this->reportHandler->initialize($this);
        Dice::globalInitialize($this->config);
        Subexpression::globalInitialize($this->config);

        $this->status = AppStatusEnum::HOLDING();

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
     * @param string $content Panel config content.
     *
     * @return int Result code.
     */
    public function setConfig(string $content): int
    {
        if (!is_array($data = json_decode($content, true))) {
            return -1;
        } elseif (false === $this->resource->getConfig()->setConfig($data)) {
            return -2;
        }

        $this->loadConfig();

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
        if ($this->getStatus()->equals(AppStatusEnum::PAUSED())) {
            // Application is already paused
            return -1;
        } elseif ($this->getStatus()->lessThan(AppStatusEnum::PAUSED())) {
            // Cannot be paused
            return -2;
        }

        $this->setStatus(AppStatusEnum::PAUSED());

        return 0;
    }

    /**
     * Rerun application.
     *
     * @return int Result code.
     */
    public function run(): int
    {
        if ($this->getStatus()->equals(AppStatusEnum::RUNNING())) {
            // Application is already running
            return -1;
        } elseif (!$this->getStatus()->equals(AppStatusEnum::PAUSED())) {
            // Cannot be rerun
            return -2;
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

        return -3;
    }

    /**
     * Reload the resources.
     *
     * @return int Result code.
     */
    public function reload(): int
    {
        $this->status = AppStatusEnum::PAUSED();

        if (!$this->resource->reload()) {
            $this->logger->critical("Reload application failed.");

            return -1;
        }

        $this->loadConfig();

        $this->status = AppStatusEnum::RUNNING();

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
        $this->setStatus(AppStatusEnum::STOPPED());

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
