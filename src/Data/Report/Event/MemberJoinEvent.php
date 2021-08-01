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
 */
final class MemberJoinEvent extends Event
{
    /** @var GroupMember The new group member. */
    public GroupMember $member;
}
