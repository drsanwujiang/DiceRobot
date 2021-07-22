<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\{Group, Operator};
use DiceRobot\Data\Report\Event;

/**
 * Class GroupAllowMemberInviteEvent
 *
 * DTO. Event of that the invitation from group member is enabled/disabled.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class GroupAllowMemberInviteEvent extends Event
{
    /** @var bool Whether the invitation from group member was enabled before. */
    public bool $origin;

    /** @var bool Whether the invitation from group member is enabled now. */
    public bool $current;

    /** @var Group The group. */
    public Group $group;

    /** @var Operator|null The operator, null if the operator is the robot. */
    public ?Operator $operator;
}
