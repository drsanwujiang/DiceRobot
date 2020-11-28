<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\{Group, Operator};
use DiceRobot\Data\Report\Event;

/**
 * Class GroupAllowAnonymousChatEvent
 *
 * DTO. Event of that anonymous chat of the group is enabled/disabled.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#%E5%8C%BF%E5%90%8D%E8%81%8A%E5%A4%A9
 */
final class GroupAllowAnonymousChatEvent extends Event
{
    /** @var bool Whether anonymous chat was enabled before. */
    public bool $origin;

    /** @var bool Whether anonymous chat is enabled now. */
    public bool $current;

    /** @var Group The group. */
    public Group $group;

    /** @var Operator|null The operator, null if the operator is the robot. */
    public ?Operator $operator;
}
