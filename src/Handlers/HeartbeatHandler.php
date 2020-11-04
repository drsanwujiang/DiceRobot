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
     * @param App $app
     * @param ApiService $api
     * @param ResourceService $resource
     * @param RobotService $robot
     * @param LoggerFactory $loggerFactory
     */
    public function __construct(
        App $app,
        ApiService $api,
        ResourceService $resource,
        RobotService $robot,
        LoggerFactory $loggerFactory
    ) {
        $this->app = $app;
        $this->api = $api;
        $this->resource = $resource;
        $this->robot = $robot;
        $this->logger = $loggerFactory->create("Handler");
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
        if ($this->resource->saveAll() && $this->checkSession() && $this->robot->update()) {
            $this->logger->info("Heartbeat finished.");
        } else {
            if ($this->app->getStatus()->equals(AppStatusEnum::RUNNING())) {
                $this->app->setStatus(AppStatusEnum::HOLDING());
            }

            $this->logger->alert("Heartbeat failed. Application status {$this->app->getStatus()}.");
        }
    }

    /**
     * Check Mirai session status and extend its effective time.
     *
     * @return bool
     */
    public function checkSession(): bool
    {
        try {
            if (0 == $code = $this->api->verifySession($this->robot->getId())->getInt("code", -1)) {
                $this->logger->info("Session verified.");

                return true;
            }

            System::sleep(1);

            $this->logger->error("Session unauthorized, code {$code}. Try to initialize.");

            // Try to initialize session
            if ($this->api->initSession($this->robot->getAuthKey(), $this->robot->getId())) {
                $this->logger->info("Session verified.");

                return true;
            } else {
                $this->logger->critical("Check session failed.");
            }
        } catch (MiraiApiException $e) {  // TODO: catch (MiraiApiException) in PHP 8
            $this->logger->alert("Check session failed, unable to call Mirai API.");
        }

        return false;
    }
}
