<?php

declare(strict_types=1);

namespace DiceRobot\Action\Event;

use DiceRobot\Action\EventAction;
use DiceRobot\Data\Report\Event;
use DiceRobot\Data\Report\Event\MemberCardChangeEvent;

/**
 * Class MemberCardChange
 *
 * Action that handles MemberCardChangeEvent.
 *
 * Update robot nickname cache of the group.
 *
 * @event MemberCardChangeEvent
 *
 * @package DiceRobot\Action\Event
 */
class MemberCardChange extends EventAction
{
    /** @var MemberCardChangeEvent Event. */
    public Event $event;

    /**
     * @inheritDoc
     */
    public function __invoke(): void
    {
        if ($this->event->member->id == $this->robot->getId()) {
            $this->robot->updateNickname($this->event->member->group->id, $this->event->current);
        }
    }
}
