<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\Group;
use DiceRobot\Data\Report\Event;

/**
 * Class GroupAllowConfessTalkEvent
 *
 * DTO. Event of that confess chat of the group is enabled/disabled.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#%E5%9D%A6%E7%99%BD%E8%AF%B4
 */
final class GroupAllowConfessTalkEvent extends Event
{
    /** @var bool Whether confess chat was enabled before. */
    public bool $origin;

    /** @var bool Whether confess chat is enabled now. */
    public bool $current;

    /** @var Group The group. */
    public Group $group;

    /** @var bool Whether the operator is the robot. */
    public bool $isByBot;
}
