<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\{GroupMember, Operator};
use DiceRobot\Data\Report\Event;

/**
 * Class MemberUnmuteEvent
 *
 * Event of that a group member is unmuted.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#%E7%BE%A4%E6%88%90%E5%91%98%E8%A2%AB%E5%8F%96%E6%B6%88%E7%A6%81%E8%A8%80%E4%BA%8B%E4%BB%B6%E8%AF%A5%E6%88%90%E5%91%98%E4%B8%8D%E6%98%AFbot
 */
final class MemberUnmuteEvent extends Event
{
    /** @var GroupMember The unmuted group member. */
    public GroupMember $member;

    /** @var Operator|null The operator, null if the operator is the robot. */
    public ?Operator $operator;
}
