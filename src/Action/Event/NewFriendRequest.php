<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\NewFriendRequestEvent;
use DiceRobot\Exception\MiraiApiException;

/**
 * Class NewFriendRequest
 *
 * Action that responds to NewFriendRequestEvent.
 *
 * Process the new friend request.
 *
 * @event NewFriendRequestEvent
 *
 * @package DiceRobot\Action\Event
 */
class NewFriendRequest extends EventAction
{
    /**
     * @var NewFriendRequestEvent $event Event
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
        // Approve request by default
        $this->api->respondToNewFriendRequestEvent(
            $this->event->eventId,
            $this->event->fromId,
            $this->event->groupId
        );
    }
}
