<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\BotJoinGroupEvent;

/**
 * Class BotLeaveKick
 *
 * Action that responds to BotLeaveEventKick.
 *
 * Report the group.
 *
 * @event BotLeaveEventKick
 *
 * @package DiceRobot\Action\Event
 */
class BotLeaveKick extends EventAction
{
    /**
     * @var BotJoinGroupEvent $event Event
     */
    public Event $event;

    /**
     * @inheritDoc
     */
    public function __invoke(): void
    {
        // Report the group
        $this->api->reportGroup(
            $this->event->group->id,
            $this->api->getToken($this->robot->getId())->token
        );
    }
}
