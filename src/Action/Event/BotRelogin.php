<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\BotReloginEvent;
use DiceRobot\Enum\AppStatusEnum;
use DiceRobot\Exception\MiraiApiException;

/**
 * Class BotRelogin
 *
 * Action that responds to BotReloginEvent.
 *
 * Initialize API service (auth a new session).
 *
 * @event BotReloginEvent
 *
 * @package DiceRobot\Action\Event
 */
class BotRelogin extends EventAction
{
    /**
     * @var BotReloginEvent $event Event
     *
     * @noinspection PhpDocFieldTypeMismatchInspection
     */
    public Event $event;

    /**
     * @inheritDoc
     *
     * @throws MiraiApiException
     */
    public function __invoke(): void
    {
        $this->logger->notice("Robot is online (relogin).");

        // Re-verify session
        if (0 == $code = $this->api->verifySession($this->robot->getId())->getInt("code", -1)) {
            $this->logger->info("Session verified.");

            // Update robot service
            if ($this->app->updateRobot()) {
                if ($this->app->getStatus()->equals(AppStatusEnum::HOLDING())) {
                    $this->app->setStatus(AppStatusEnum::RUNNING());
                }

                return;
            }
        } else {
            // Re-verify failed
            $this->logger->warning("Session unauthorized, code {$code}. Try to initialize.");

            // Try to initialize API service, then update robot service
            if ($this->api->initialize($this->robot->getAuthKey(), $this->robot->getId()) && $this->app->updateRobot()
            ) {
                if ($this->app->getStatus()->equals(AppStatusEnum::HOLDING()))
                    $this->app->setStatus(AppStatusEnum::RUNNING());

                return;
            }
        }

        // Failed
        if ($this->app->getStatus()->equals(AppStatusEnum::RUNNING())) {
            $this->app->setStatus(AppStatusEnum::HOLDING());
        }
    }
}
