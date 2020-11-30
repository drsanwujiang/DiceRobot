<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\GroupMember;
use DiceRobot\Data\Report\Event;

/**
 * Class MemberLeaveEventQuit
 *
 * DTO. Event of that a group member quit the group.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#%E6%88%90%E5%91%98%E4%B8%BB%E5%8A%A8%E7%A6%BB%E7%BE%A4%E8%AF%A5%E6%88%90%E5%91%98%E4%B8%8D%E6%98%AFbot
 */
final class MemberLeaveEventQuit extends Event
{
    /** @var GroupMember The group member quit. */
    public GroupMember $member;
}
