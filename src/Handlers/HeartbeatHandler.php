<?php

declare(strict_types=1);

namespace DiceRobot\Handlers;

use Co\System;
use DiceRobot\App;
use DiceRobot\Enum\AppStatusEnum;
use DiceRobot\Exception\MiraiApiException;
use DiceRobot\Factory\LoggerFactory;
use DiceRobot\Service\{ApiService, ResourceService, RobotService};
use Psr\Log\LoggerInterface;

/**
 * Class HeartbeatHandler
 *
 * The heartbeat handler.
 *
 * @package DiceRobot\Handlers
 */
class HeartbeatHandler
{
    /** @var App Application */
    protected App $app;

    /** @var ApiService API service */
    protected ApiService $api;

    /** @var ResourceService Data service */
    protected ResourceService $resource;

    /** @var RobotService Robot service */
    protected RobotService $robot;

    /** @var LoggerInterface Logger */
    protected LoggerInterface $logger;

    /**
     * The constructor.
     *
     * @param ApiService $api
     * @param ResourceService $resource
     * @param RobotService $robot
     * @param LoggerFactory $loggerFactory
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
        $this->logger = $loggerFactory->create("Handler");
    }

    /**
     * Initialize heartbeat handler.
     *
     * @param App $app
     */
    public function initialize(App $app): void
    {
        $this->app = $app;
    }

    /**
     * Handle heartbeat.
     */
    public function handle(): void
    {
        $this->logger->info("Heartbeat started.");

        // Validate
        if (!$this->app->getStatus()->equals(AppStatusEnum::RUNNING())) {
            $this->logger->info("Heartbeat skipped. Application status {$this->app->getStatus()}.");

            return;
        }

        // Heartbeat
        if ($this->resource->saveAll() && $this->prolongSession() && $this->robot->update()) {
            $this->logger->info("Heartbeat finished.");
        } else {
            if ($this->app->getStatus()->equals(AppStatusEnum::RUNNING())) {
                $this->app->setStatus(AppStatusEnum::HOLDING());
            }

            $this->logger->alert("Heartbeat failed. Application status {$this->app->getStatus()}.");
        }
    }

    /**
     * Prolong Mirai session.
     *
     * @return bool
     */
    public function prolongSession(): bool
    {
        try {
            if (0 == $code = $this->api->fetchMessage(10)->getInt("code", -1)) {
                $this->logger->info("Session prolonged.");

                return true;
            }

            $this->logger->error("Failed to prolong session, code {$code}.");

            System::sleep(1);

            $this->logger->notice("Try to initialize session.");

            // Try to initialize session
            if ($this->api->initSession($this->robot->getAuthKey(), $this->robot->getId())) {
                return true;
            } else {
                $this->logger->critical("Failed to initialize session.");
            }
        } catch (MiraiApiException $e) {  // TODO: catch (MiraiApiException) in PHP 8
            $this->logger->alert("Check session failed, unable to call Mirai API.");
        }

        return false;
    }
}
