<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\GroupMember;
use DiceRobot\Data\Report\Event;

/**
 * Class MemberJoinEvent
 *
 * DTO. Event of that a new member joined the group.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#%E6%96%B0%E4%BA%BA%E5%85%A5%E7%BE%A4%E7%9A%84%E4%BA%8B%E4%BB%B6
 */
final class MemberJoinEvent extends Event
{
    /** @var GroupMember The new group member. */
    public GroupMember $member;
}
