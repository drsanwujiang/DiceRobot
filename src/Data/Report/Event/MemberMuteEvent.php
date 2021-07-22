<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\{GroupMember, Operator};
use DiceRobot\Data\Report\Event;

/**
 * Class MemberMuteEvent
 *
 * Event of that a group member is muted.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class MemberMuteEvent extends Event
{
    /** @var int Muting duration. */
    public int $durationSeconds;

    /** @var GroupMember The muted group member. */
    public GroupMember $member;

    /** @var Operator|null The operator, null if the operator is the robot. */
    public ?Operator $operator;
}
