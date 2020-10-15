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
        // If this group is delinquent, reject the request
        $this->api->respondToBotInvitedJoinGroupRequestEvent(
            $this->event->eventId,
            $this->event->fromId,
            $this->event->groupId,
            (int) $delinquent = $this->queryGroup(),
            $delinquent ? $this->config->getString("reply.botInvitedJoinGroupRequestRejected") : ""
        );
    }

    /**
     * Query if this group is delinquent.
     *
     * @return bool Delinquent
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    private function queryGroup(): bool
    {
        return $this->api->queryGroup(
            $this->event->groupId,
            $this->api->auth(
                $this->robot->getId()
            )->token
        )->state;
    }
}
