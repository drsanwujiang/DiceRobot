<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\BotOfflineEventDropped;

/**
 * Class BotOfflineDropped
 *
 * Action that responds to BotOfflineEventDropped.
 *
 * Hold application (HOLDING).
 *
 * @event BotOfflineEventDropped
 *
 * @package DiceRobot\Action\Event
 */
class BotOfflineDropped extends EventAction
{
    /**
     * @var BotOfflineEventDropped $event Event.
     */
    public Event $event;

    /**
     * @inheritDoc
     */
    public function __invoke(): void
    {
        $this->logger->notice("Bot is offline (dropped).");

        $this->heartbeat->disable();
    }
}
