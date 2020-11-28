<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\GroupMember;
use DiceRobot\Data\Report\Event;

/**
 * Class MemberPermissionChangeEvent
 *
 * DTO. Event of that a group member's permission has changed.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#%E7%BE%A4%E5%A4%B4%E8%A1%94%E6%94%B9%E5%8A%A8%E5%8F%AA%E6%9C%89%E7%BE%A4%E4%B8%BB%E6%9C%89%E6%93%8D%E4%BD%9C%E9%99%90%E6%9D%83
 */
final class MemberPermissionChangeEvent extends Event
{
    /** @var string Original permission. */
    public string $origin;

    /** @var string Current permission. */
    public string $current;

    /** @var GroupMember The group member. */
    public GroupMember $member;
}
