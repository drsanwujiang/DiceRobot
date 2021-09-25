<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\FriendInputStatusChangedEvent;

/**
 * Class FriendInputStatusChanged
 *
 * Action that handles FriendInputStatusChangedEvent
 *
 * Check friend list.
 *
 * @event FriendInputStatusChangedEvent
 *
 * @package DiceRobot\Action\Event
 */
class FriendInputStatusChanged extends EventAction
{
    /** @var FriendInputStatusChangedEvent $event Event. */
    public Event $event;

    /**
     * @inheritDoc
     */
    public function __invoke(): void
    {
        // Check if friend exists
        if (!$this->robot->hasFriend($this->event->friend->id)) {
            $this->robot->updateFriends($this->api->getFriendList()->getArray("data"));
        }
    }
}
