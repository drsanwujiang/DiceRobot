<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\BotReloginEvent;

/**
 * Class BotRelogin
 *
 * Action that handles BotReloginEvent.
 *
 * Enable application.
 *
 * @event BotReloginEvent
 *
 * @package DiceRobot\Action\Event
 */
class BotRelogin extends EventAction
{
    /** @var BotReloginEvent $event Event. */
    public Event $event;

    /**
     * @inheritDoc
     */
    public function __invoke(): void
    {
        $this->logger->notice("Bot is online (relogin).");

        $this->app->enable();
    }
}
