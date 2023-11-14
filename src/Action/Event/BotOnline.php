<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\BotOnlineEvent;

/**
 * Class BotOnline
 *
 * Action that handles BotOnlineEvent.
 *
 * Enable application.
 *
 * @event BotOnlineEvent
 *
 * @package DiceRobot\Action\Event
 */
class BotOnline extends EventAction
{
    /** @var BotOnlineEvent $event Event. */
    public Event $event;

    /**
     * @inheritDoc
     */
    public function __invoke(): void
    {
        $this->logger->notice("Bot is online (login).");

        $this->app->enable();
    }
}
