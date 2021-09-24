<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\{Group, GroupMember};
use DiceRobot\Data\Report\Event;

/**
 * Class GroupMuteAllEvent
 *
 * DTO. Event of that all the group members are muted/unmuted.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class GroupMuteAllEvent extends Event
{
    /** @var bool Whether all group members were muted before. */
    public bool $origin;

    /** @var bool Whether all group members are muted now. */
    public bool $current;

    /** @var Group The group. */
    public Group $group;

    /** @var GroupMember|null The operator, null if the operator is the robot. */
    public ?GroupMember $operator;
}
