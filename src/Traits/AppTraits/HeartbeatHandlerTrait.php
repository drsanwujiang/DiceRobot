<?php

declare(strict_types=1);

namespace DiceRobot\Traits\AppTraits;

use co;
use DiceRobot\Enum\AppStatusEnum;
use DiceRobot\Exception\MiraiApiException;
use DiceRobot\Exception\ApiException\{InternalErrorException, NetworkErrorException, UnexpectedErrorException};
use DiceRobot\Service\{ApiService, ResourceService, RobotService};

/**
 * Trait HeartbeatHandlerTrait
 *
 * The heartbeat handler trait.
 *
 * @package DiceRobot\Traits
 */
trait HeartbeatHandlerTrait
{
    /** @var ApiService API service */
    protected ApiService $api;

    /** @var ResourceService Data service */
    protected ResourceService $resource;

    /** @var RobotService Robot service */
    protected RobotService $robot;

    use StatusTrait;

    /**
     * Handle heartbeat.
     */
    public function heartbeat(): void
    {
        $this->logger->info("Heartbeat started.");

        /** Validate */
        if (!$this->getStatus()->equals(AppStatusEnum::RUNNING()))
        {
            $this->logger->info("Heartbeat skipped. Application status {$this->getStatus()}.");

            return;
        }

        /** Heartbeat */
        if ($this->resource->saveAll() && $this->checkSession() && $this->updateRobot())
        {
            $this->logger->info("Heartbeat finished.");
        }
        else
        {
            if ($this->getStatus()->equals(AppStatusEnum::RUNNING()))
                $this->setStatus(AppStatusEnum::HOLDING());

            $this->logger->alert("Heartbeat failed. Application status {$this->getStatus()}.");
        }
    }

    /**
     * Check Mirai session status and extend its effective time.
     *
     * @return bool
     */
    public function checkSession(): bool
    {
        try
        {
            // Retry 3 times, for the case that heartbeat happens between BotOfflineEventDropped and BotReloginEvent
            for ($i = 0; $i < 3; $i++)
            {
                if (0 == $code = $this->api->verifySession($this->robot->getId())->getInt("code", -1))
                {
                    $this->logger->info("Session verified.");

                    return true;
                }
                else
                {
                    $this->logger->warning("Session unauthorized, code {$code}. Retry.");

                    Co::sleep(1);
                }
            }

            $this->logger->error("Session unauthorized, failed to retry. Try to initialize.");

            // Try to initialize API service
            if ($this->api->initialize($this->robot->getAuthKey(), $this->robot->getId()))
            {
                $this->logger->info("Session verified.");

                return true;
            }
            else
                $this->logger->critical("Check session failed.");
        }
            // Call Mirai APIs failed
        catch (MiraiApiException $e)  // TODO: catch (MiraiApiException) in PHP 8
        {
            $this->logger->alert("Check session failed, unable to call Mirai API.");
        }

        return false;
    }

    /**
     * Update robot service.
     *
     * @return bool
     */
    public function updateRobot(): bool
    {
        try
        {
            // Refresh friend list
            $this->robot->updateFriends($this->api->getFriendList()->all());
            // Refresh group list
            $this->robot->updateGroups($this->api->getGroupList()->all());
            // Refresh robot nickname
            $this->robot->updateNickname($this->api->getNickname($this->robot->getId())->nickname);
            // Report to DiceRobot API
            $this->api->updateRobotAsync($this->robot->getId());

            $this->logger->info("Robot updated.");

            return true;
        }
        // Call Mirai APIs failed
        catch (MiraiApiException | InternalErrorException | NetworkErrorException | UnexpectedErrorException $e)  // TODO: catch (MiraiApiException) in PHP 8
        {
            $this->logger->alert("Update robot failed, unable to call Mirai API.");

            return false;
        }
    }
}
