<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\BotOfflineEventForce;

/**
 * Class BotOfflineForce
 *
 * Action that handles BotOfflineEventForce.
 *
 * Disable application.
 *
 * @event BotOfflineEventForce
 *
 * @package DiceRobot\Action\Event
 */
class BotOfflineForce extends EventAction
{
    /** @var BotOfflineEventForce $event Event. */
    public Event $event;

    /**
     * @inheritDoc
     */
    public function __invoke(): void
    {
        $this->logger->warning("Bot is offline (forced).");

        $this->app->disable();
    }
}
