<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\{Group, Operator};
use DiceRobot\Data\Report\Event;

/**
 * Class GroupEntranceAnnouncementChangeEvent
 *
 * DTO. Event of that the group's entrance announcement changed.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class GroupEntranceAnnouncementChangeEvent extends Event
{
    /** @var string Original entrance announcement. */
    public string $origin;

    /** @var string Current entrance announcement. */
    public string $current;

    /** @var Group The group. */
    public Group $group;

    /** @var Operator|null The operator, null if the operator is the robot. */
    public ?Operator $operator;
}
