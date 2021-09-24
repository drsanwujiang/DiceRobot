<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\GroupMember;
use DiceRobot\Data\Report\Event;

/**
 * Class BotUnmuteEvent
 *
 * DTO. Event of that robot is unmuted in the group.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class BotUnmuteEvent extends Event
{
    /** @var GroupMember The operator. */
    public GroupMember $operator;
}
