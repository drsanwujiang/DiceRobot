<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\{Group, Operator};
use DiceRobot\Data\Report\Event;

/**
 * Class GroupNameChangeEvent
 *
 * DTO. Event of that the group's name has changed.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#%E6%9F%90%E4%B8%AA%E7%BE%A4%E5%90%8D%E6%94%B9%E5%8F%98
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
