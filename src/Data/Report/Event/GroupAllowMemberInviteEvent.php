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
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#%E5%85%81%E8%AE%B8%E7%BE%A4%E5%91%98%E9%82%80%E8%AF%B7%E5%A5%BD%E5%8F%8B%E5%8A%A0%E7%BE%A4
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
