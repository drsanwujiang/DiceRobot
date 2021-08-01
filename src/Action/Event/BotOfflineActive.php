<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\BotOfflineEventActive;

/**
 * Class BotOfflineActive
 *
 * Action that handles BotOfflineEventActive.
 *
 * Hold application (HOLDING).
 *
 * @event BotOfflineEventActive
 *
 * @package DiceRobot\Action\Event
 */
class BotOfflineActive extends EventAction
{
    /** @var BotOfflineEventActive $event Event. */
    public Event $event;

    /**
     * @inheritDoc
     */
    public function __invoke(): void
    {
        $this->logger->warning("Bot is offline (active).");

        $this->heartbeat->disable();
    }
}
