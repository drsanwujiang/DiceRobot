<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\Member;
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
    /** @var Member The group member quit. */
    public Member $member;
}
