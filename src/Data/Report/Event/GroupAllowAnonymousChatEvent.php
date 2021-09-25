<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\{Group, GroupMember};
use DiceRobot\Data\Report\Event;

/**
 * Class GroupAllowAnonymousChatEvent
 *
 * DTO. Event of that anonymous chat of the group is enabled/disabled.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class GroupAllowAnonymousChatEvent extends Event
{
    /** @var bool Whether anonymous chat was enabled before. */
    public bool $origin;

    /** @var bool Whether anonymous chat is enabled now. */
    public bool $current;

    /** @var Group The group. */
    public Group $group;

    /** @var GroupMember|null The operator, null if the operator is the robot. */
    public ?GroupMember $operator;
}
