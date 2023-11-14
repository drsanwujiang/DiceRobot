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
        $this->logger->notice("Robot received friend request from {$this->event->fromId}.");

        if (!$this->checkListen()) {
            return;
        }

        $approval = $this->checkApprove();
        $operation = $approval ? 0 : 1;
        $message = "";

        $this->api->handleNewFriendRequestEvent(
            $this->event->eventId,
            $this->event->fromId,
            $this->event->groupId,
            $operation,
            $message
        );

        if ($approval) {
            $this->logger->notice("Friend request from {$this->event->fromId} has been approved.");

            // If friend doesn't exist, update friend list
            if (!$this->robot->hasFriend($this->event->fromId)) {
                $this->robot->updateFriends($this->api->getFriendList()->getArray("data"));
            }
        } else {
            $this->logger->notice("Friend request from {$this->event->fromId} has been rejected.");
        }
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
        return $this->config->getStrategy("approveFriendRequest");
    }
}
