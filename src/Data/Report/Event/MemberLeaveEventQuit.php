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
 */
final class MemberLeaveEventQuit extends Event
{
    /** @var GroupMember The group member quit. */
    public GroupMember $member;
}
