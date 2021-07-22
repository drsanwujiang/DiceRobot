<?php

declare(strict_types=1);

namespace DiceRobot\Service;

use DiceRobot\AppStatus;
use DiceRobot\Exception\MiraiApiException;
use DiceRobot\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;
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

    /** @var int Timer ID. */
    protected int $timerId = -1;

    /**
     * The constructor.
     *
     * @param ApiService $api API service.
     * @param ResourceService $resource Resource service.
     * @param RobotService $robot Robot service.
     * @param LoggerFactory $loggerFactory Logger factory.
     */
    public function __construct(
        ApiService $api,
        ResourceService $resource,
        RobotService $robot,
        LoggerFactory $loggerFactory
    ) {
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

        if ($this->timerId >= 0) {
            Timer::clear($this->timerId);
        }

        $this->logger->debug("Heartbeat service destructed.");
    }

    /**
     * Initialize service.
     */
    public function initialize(): void
    {
        $this->initTimerId = Timer::after(3000, function () {
            $this->logger->notice("Try to enable heartbeat.");

            $this->enable();
        });

        $this->logger->notice("Heartbeat service initialized.");
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
        // Check timer existence.
        if ($this->initTimerId >= 0) {
            Timer::clear($this->initTimerId);
            $this->initTimerId = -1;
        }

        if ($this->timerId >= 0) {
            Timer::clear($this->timerId);
            $this->timerId = -1;
        }

        try {
            // Check logined
            $result = $this->api->getSessionInfo();
        } catch (MiraiApiException $e) {  // TODO: catch (MiraiApiException) in PHP 8
            $this->logger->alert("Enable heartbeat failed, unable to call Mirai API.");

            return false;
        }

        if (0 != $result->getInt("code", -1)) {
            $this->logger->warning("Enable heartbeat failed, bot not logined.");

            return false;
        } elseif ($result->getString("data.sessionKey") != "SINGLE_SESSION") {
            $this->logger->alert("Mirai not work in single mode.");

            return false;
        }

        // Heartbeat every 5 minutes
        $this->timerId = Timer::tick(300000, function () {
            $this->heartbeat();
        });

        $this->logger->notice("Heartbeat enabled.");

        $this->heartbeat();
        AppStatus::run();

        return true;
    }

    /**
     * Disable heartbeat.
     */
    public function disable(): void
    {
        Timer::clear($this->timerId);
        $this->timerId = -1;

        $this->logger->notice("Heartbeat disabled.");

        AppStatus::hold();
    }

    /**
     * Heartbeat logic.
     */
    protected function heartbeat(): void
    {
        $this->logger->info("Heartbeat started.");

        if ($this->resource->save() && $this->robot->update()) {
            $this->logger->info("Heartbeat finished.");
        } else {
            $this->logger->alert("Heartbeat failed.");

            $this->disable();
        }
    }
}
