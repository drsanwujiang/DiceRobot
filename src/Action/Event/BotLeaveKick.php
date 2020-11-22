<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\BotJoinGroupEvent;
use DiceRobot\Exception\ApiException\{InternalErrorException, NetworkErrorException, UnexpectedErrorException};

/**
 * Class BotLeaveKick
 *
 * Action that responds to BotLeaveEventKick.
 *
 * Report the group ID.
 *
 * @event BotLeaveEventKick
 *
 * @package DiceRobot\Action\Event
 */
class BotLeaveKick extends EventAction
{
    /**
     * @var BotJoinGroupEvent $event Event
     *
     * @noinspection PhpDocFieldTypeMismatchInspection
     */
    public Event $event;

    /**
     * @inheritDoc
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    public function __invoke(): void
    {
        // Submit this group to public database
        $this->api->submitGroup(
            $this->event->group->id,
            $this->api->auth($this->robot->getId())->token
        );
    }
}
