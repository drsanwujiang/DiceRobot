<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\BotOnlineEvent;
use DiceRobot\Enum\AppStatusEnum;
use DiceRobot\Exception\MiraiApiException;

/**
 * Class BotOnline
 *
 * Action that responds to BotOnlineEvent.
 *
 * Initialize API service (auth a new session).
 *
 * @event BotOnlineEvent
 *
 * @package DiceRobot\Action\Event
 */
class BotOnline extends EventAction
{
    /**
     * @var BotOnlineEvent $event Event
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
        $this->logger->notice("Robot is online (login).");

        // Try to initialize API service, then update robot service
        if ($this->api->initialize($this->robot->getAuthKey(), $this->robot->getId()) && $this->app->updateRobot()) {
            if ($this->app->getStatus()->equals(AppStatusEnum::HOLDING()))
                $this->app->setStatus(AppStatusEnum::RUNNING());

            return;
        }

        // Failed
        if ($this->app->getStatus()->equals(AppStatusEnum::RUNNING())) {
            $this->app->setStatus(AppStatusEnum::HOLDING());
        }
    }
}
