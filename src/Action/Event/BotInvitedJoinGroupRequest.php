<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\BotInvitedJoinGroupRequestEvent;

/**
 * Class BotInvitedJoinGroupRequest
 *
 * Action that handles BotInvitedJoinGroupRequestEvent.
 *
 * Approve the request when the group is normal, or reject the request when the group is delinquent.
 *
 * @event BotInvitedJoinGroupRequestEvent
 *
 * @package DiceRobot\Action\Event
 */
class BotInvitedJoinGroupRequest extends EventAction
{
    /** @var BotInvitedJoinGroupRequestEvent $event Event. */
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

        if ($operation == 0 && $this->checkRejectWhenDelinquent() && !$this->queryGroup()) {
            // Group is in black list, reject the request
            $operation = 1;
            $message = $this->config->getStrategy("botInvitedJoinGroupRequestRejected");
        }

        $this->api->handleBotInvitedJoinGroupRequestEvent(
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
        return $this->config->getStrategy("listenBotInvitedJoinGroupRequestEvent");
    }

    /**
     * Check whether this request should be approved.
     *
     * @return bool Strategy.
     */
    protected function checkApprove(): bool
    {
        return $this->config->getStrategy("approveGroupRequest");
    }

    /**
     * Check whether this request should be rejected when the group is delinquent.
     *
     * @return bool Strategy.
     */
    protected function checkRejectWhenDelinquent(): bool
    {
        return $this->config->getStrategy("rejectDelinquentGroupRequest");
    }

    /**
     * Request to query the group state, normal or delinquent.
     *
     * @return bool Group state, normal or delinquent.
     */
    protected function queryGroup(): bool
    {
        return $this->api->queryGroup(
            $this->event->groupId,
            $this->api->getToken($this->robot->getId())->token
        )->state;
    }
}
