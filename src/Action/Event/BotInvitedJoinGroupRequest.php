<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\BotInvitedJoinGroupRequestEvent;
use DiceRobot\Exception\MiraiApiException;
use DiceRobot\Exception\ApiException\{InternalErrorException, NetworkErrorException, UnexpectedErrorException};

/**
 * Class BotInvitedJoinGroupRequest
 *
 * Action that responds to BotInvitedJoinGroupRequestEvent.
 *
 * Approve the request when the group is normal, or reject the request when the group is delinquent.
 *
 * @event BotInvitedJoinGroupRequestEvent
 *
 * @package DiceRobot\Action\Event
 */
class BotInvitedJoinGroupRequest extends EventAction
{
    /**
     * @var BotInvitedJoinGroupRequestEvent $event Event
     *
     * @noinspection PhpDocFieldTypeMismatchInspection
     */
    public Event $event;

    /**
     * @inheritDoc
     *
     * @throws InternalErrorException|MiraiApiException|NetworkErrorException|UnexpectedErrorException|
     */
    public function __invoke(): void
    {
        if (!$this->checkListen()) {
            return;
        }

        $operation = $this->checkApprove() ? 0 : 1;
        $message = "";

        if ($operation == 0 && $this->checkRejectWhenDelinquent() && $this->queryGroup()) {
            // Group is in black list, reject the request
            $operation = 1;
            $message = $this->config->getString("reply.botInvitedJoinGroupRequestRejected");
        }

        $this->api->respondToBotInvitedJoinGroupRequestEvent(
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
     * @return bool Listened
     */
    protected function checkListen(): bool
    {
        return $this->config->getBool("strategy.listenBotInvitedJoinGroupRequestEvent");
    }

    /**
     * Check whether this request should be approved.
     *
     * @return bool Approved
     */
    protected function checkApprove(): bool
    {
        return $this->config->getBool("strategy.approveGroupRequest");
    }

    /**
     * Check whether this request should be rejected when the group is delinquent.
     *
     * @return bool Rejected
     */
    protected function checkRejectWhenDelinquent(): bool
    {
        return $this->config->getBool("strategy.rejectDelinquentGroupRequest");
    }

    /**
     * Query whether this group is delinquent.
     *
     * @return bool Delinquent
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    protected function queryGroup(): bool
    {
        return $this->api->queryGroup(
            $this->event->groupId,
            $this->api->auth($this->robot->getId())->token
        )->state;
    }
}
