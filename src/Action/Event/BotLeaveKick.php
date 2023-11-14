<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\BotJoinGroupEvent;

/**
 * Class BotLeaveKick
 *
 * Action that handles BotLeaveEventKick.
 *
 * Report the group.
 *
 * @event BotLeaveEventKick
 *
 * @package DiceRobot\Action\Event
 */
class BotLeaveKick extends EventAction
{
    /** @var BotJoinGroupEvent $event Event */
    public Event $event;

    /**
     * @inheritDoc
     */
    public function __invoke(): void
    {
        $this->logger->warning("Robot is kicked out of group {$this->event->group->id}.");

        // Report the group
        $this->api->reportGroup(
            $this->event->group->id,
            $this->api->getToken($this->robot->getId())->token
        );

        $this->logger->notice("Group {$this->event->group->id} has been reported.");
    }
}
