<?php

declare(strict_types=1);

namespace DiceRobot\Service;

use DiceRobot\App;
use DiceRobot\Data\Config;
use DiceRobot\Exception\MiraiApiException;
use DiceRobot\Factory\LoggerFactory;
use DiceRobot\Util\Environment;
use Psr\Log\LoggerInterface;
use Swoole\Coroutine\System;
use Swoole\Timer;

/**
 * Class HeartbeatService
 *
 * Heartbeat service.
 *
 * @package DiceRobot\Service
 */
class HeartbeatService
{
    /** @var Config DiceRobot config. */
    protected Config $config;

    /** @var App Application. */
    protected App $app;

    /** @var ApiService API service. */
    protected ApiService $api;

    /** @var ResourceService Resource service. */
    protected ResourceService $resource;

    /** @var RobotService Robot service. */
    protected RobotService $robot;

    /** @var LoggerInterface Logger. */
    protected LoggerInterface $logger;

    /** @var int Initialization timer ID. */
    protected int $initTimerId = -1;

    /** @var int Heartbeat timer ID. */
    protected int $heartbeatTimerId = -1;

    /** @var int Auto reenable timer ID. */
    protected int $reenableTimerId = -1;

    /** @var bool Last heartbeat success flag. */
    protected bool $lastHeartbeat = false;

    /**
     * The constructor.
     *
     * @param Config $config DiceRobot config.
     * @param ApiService $api API service.
     * @param ResourceService $resource Resource service.
     * @param RobotService $robot Robot service.
     * @param LoggerFactory $loggerFactory Logger factory.
     */
    public function __construct(
        Config $config,
        ApiService $api,
        ResourceService $resource,
        RobotService $robot,
        LoggerFactory $loggerFactory
    ) {
        $this->config = $config;
        $this->api = $api;
        $this->resource = $resource;
        $this->robot = $robot;

        $this->logger = $loggerFactory->create("Heartbeat");

        $this->logger->debug("Heartbeat service created.");
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
        if ($this->initTimerId >= 0) {
            Timer::clear($this->initTimerId);
        }

        if ($this->heartbeatTimerId >= 0) {
            Timer::clear($this->heartbeatTimerId);
        }

        $this->logger->debug("Heartbeat service destructed.");
    }

    /**
     * Initialize service.
     */
    public function initialize(App $app): void
    {
        $this->app = $app;

        $this->logger->info("Heartbeat service initialized.");
    }

    /**
     * Enable heartbeat.
     *
     * @return bool Success.
     *
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function enable(): bool
    {
        // Check initialization timer.
        if ($this->initTimerId >= 0) {
            Timer::clear($this->initTimerId);
            $this->initTimerId = -1;
        }

        // Check heartbeat timer.
        if ($this->heartbeatTimerId >= 0) {
            Timer::clear($this->heartbeatTimerId);
            $this->heartbeatTimerId = -1;
        }

        $this->lastHeartbeat = false; // Reset last heartbeat flag

        try {
            $result = $this->api->getSessionInfo();
        } catch (MiraiApiException $e) {  // TODO: catch (MiraiApiException) in PHP 8
            $this->logger->critical("Failed to enable heartbeat, unable to call Mirai API.");

            return false;
        }

        // Check Mirai
        if (0 != $result->getInt("code", -1)) {
            $this->logger->warning("Failed to enable heartbeat, bot not logined.");

            return false;
        } elseif ($result->getString("data.sessionKey") != "SINGLE_SESSION") {
            $this->logger->critical("Mirai not work in single mode.");

            return false;
        }

        // Heartbeat every 5 minutes
        $this->heartbeatTimerId = Timer::tick(300000, function () {
            $this->heartbeat();
        });

        $this->logger->notice("Heartbeat enabled.");

        // Try to heartbeat
        if (!$this->heartbeat()) {
            return false;
        }

        // Cancel auto reenable task
        if ($this->reenableTimerId >= 0) {
            Timer::clear($this->reenableTimerId);
            $this->reenableTimerId = -1;
        }

        return true;
    }

    /**
     * Disable heartbeat.
     *
     * @param bool $logError Whether error should be logged.
     */
    public function disable(bool $logError = true): void
    {
        Timer::clear($this->heartbeatTimerId);
        $this->heartbeatTimerId = -1;

        if ($logError) {
            $this->logger->warning("Heartbeat disabled.");
        }
    }

    /**
     * Heartbeat logic. Perform periodic tasks, such as saving resources, check Mirai status, update bot info and
     * report to DiceRobot server etc.
     *
     * @return bool Success.
     */
    protected function heartbeat(): bool
    {
        $this->logger->info("Heartbeat started.");

        if ($this->resource->save() && $this->checkSession() && $this->robot->update()) {
            // Function normally, report
            $this->api->updateRobotAsync($this->robot->getId());

            $this->logger->info("Heartbeat finished.");

            return $this->lastHeartbeat = true;
        } else {
            $this->logger->critical("Heartbeat failed.");

            $this->app->disable();  // Disable application, not just disable this service

            // Only auto restart when last heartbeat succeeded
            if ($this->lastHeartbeat && $this->config->getBool("panel.autoRestart")) {
                // Auto restart Mirai
                $this->logger->notice("Auto restart Mirai.");

                $this->restartMirai();

                // If Mirai successfully restarted and logined, heartbeat will be enabled and the timer will be cleared
                $this->reenableTimerId = Timer::after(30000, function () {
                    $this->reenable();
                });
            }

            return $this->lastHeartbeat = false;
        }
    }

    /**
     * Restart Mirai.
     */
    protected function restartMirai(): void
    {
        $code = $signal = -1;
        $output = "";

        extract(System::exec(
            Environment::getSystemctl() . " restart {$this->config->getString("mirai.service.name")}"
        ), EXTR_OVERWRITE);

        if ($code == 0) {
            $this->logger->notice("Mirai restarted.");
        } else {
            $this->logger->critical(
                "Failed to restart Mirai. Code {$code}, signal {$signal}, output message: {$output}"
            );
        }
    }

    /**
     * Reenable heartbeat.
     */
    protected function reenable(): void
    {
        $this->logger->notice("Try to reenable heartbeat.");

        if (!$this->enable()) {
            $this->logger->alert("Auto reenable heartbeat failed.");

            if ($this->config->getBool("panel.malfunctionReport") &&
                !empty($dicerobotToken = $this->config->getString("panel.token"))
            ) {
                if ($this->api->reportMalfunction($this->robot->getId(), $dicerobotToken)->code == 0) {
                    $this->logger->notice("Network malfunction reported.");
                } else {
                    $this->logger->error("Failed to report network malfunction.");
                }
            }
        }
    }

    /**
     * Check Mirai session.
     *
     * @return bool Success.
     *
     * @noinspection PhpRedundantCatchClauseInspection
     */
    protected function checkSession(): bool
    {
        try {
            $result = $this->api->getSessionInfo();
        } catch (MiraiApiException $e) {  // TODO: catch (MiraiApiException) in PHP 8
            $this->logger->error("Check session failed.");

            return false;
        }

        if ($result->getInt("code") != 0) {
            $this->logger->error("Check session failed.");

            return false;
        }

        return true;
    }
}
