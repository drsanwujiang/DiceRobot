<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\NewFriendRequestEvent;

/**
 * Class NewFriendRequest
 *
 * Action that handles NewFriendRequestEvent.
 *
 * Process the new friend request.
 *
 * @event NewFriendRequestEvent
 *
 * @package DiceRobot\Action\Event
 */
class NewFriendRequest extends EventAction
{
    /** @var NewFriendRequestEvent $event Event. */
    public Event $event;

    /**
     * @inheritDoc
     */
    public function __invoke(): void
    {
        if (!$this->checkListen()) {
            return;
        }

        $operation = $this->checkApprove() ? 0 : 1;
        $message = "";

        // Approve request by default
        $this->api->handleNewFriendRequestEvent(
            $this->event->eventId,
            $this->event->fromId,
            $this->event->groupId,
            $operation,
            $message
        );
    }

    /**
     * @inheritDoc
     *
     * @return bool Listen strategy.
     */
    protected function checkListen(): bool
    {
        return $this->config->getStrategy("listenNewFriendRequestEvent");
    }

    /**
     * Check whether this request should be approved.
     *
     * @return bool Strategy.
     */
    protected function checkApprove(): bool
    {
        return $this->config->getBool("approveFriendRequest");
    }
}
