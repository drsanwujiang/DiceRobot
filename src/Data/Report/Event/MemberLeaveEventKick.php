<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\{GroupMember, Operator};
use DiceRobot\Data\Report\Event;

/**
 * Class MemberLeaveEventKick
 *
 * DTO. Event of that a group member is kicked out of the group.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#%E6%88%90%E5%91%98%E8%A2%AB%E8%B8%A2%E5%87%BA%E7%BE%A4%E8%AF%A5%E6%88%90%E5%91%98%E4%B8%8D%E6%98%AFbot
 */
final class MemberLeaveEventKick extends Event
{
    /** @var GroupMember The kicked group member. */
    public GroupMember $member;

    /** @var Operator|null The operator, null if the operator is the robot. */
    public ?Operator $operator;
}
