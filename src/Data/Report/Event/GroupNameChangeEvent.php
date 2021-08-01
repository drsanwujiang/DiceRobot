<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\{Group, Operator};
use DiceRobot\Data\Report\Event;

/**
 * Class GroupNameChangeEvent
 *
 * DTO. Event of that the group name changed.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class GroupNameChangeEvent extends Event
{
    /** @var string Original group name. */
    public string $origin;

    /** @var string Current group name. */
    public string $current;

    /** @var Group The group. */
    public Group $group;

    /** @var Operator|null The operator, null if the operator is the robot. */
    public ?Operator $operator;
}
