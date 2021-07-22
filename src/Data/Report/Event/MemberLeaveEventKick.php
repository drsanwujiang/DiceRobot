<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\{GroupMember, Operator};
use DiceRobot\Data\Report\Event;

/**
 * Class MemberLeaveEventKick
 *
 * DTO. Event of that a group member is kicked out of the group.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class MemberLeaveEventKick extends Event
{
    /** @var GroupMember The kicked group member. */
    public GroupMember $member;

    /** @var Operator|null The operator, null if the operator is the robot. */
    public ?Operator $operator;
}
