<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\GroupMember;
use DiceRobot\Data\Report\Event;

/**
 * Class MemberUnmuteEvent
 *
 * Event of that a group member is unmuted.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class MemberUnmuteEvent extends Event
{
    /** @var GroupMember The unmuted group member. */
    public GroupMember $member;

    /** @var GroupMember|null The operator, null if the operator is the robot. */
    public ?GroupMember $operator;
}
