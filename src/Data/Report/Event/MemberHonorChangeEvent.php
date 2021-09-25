<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\GroupMember;
use DiceRobot\Data\Report\Event;

/**
 * Class MemberHonorChangeEvent
 *
 * DTO. Event of that a group member's honor changed.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class MemberHonorChangeEvent extends Event
{
    /** @var string Honor action. */
    public string $action;

    /** @var string Honor name. */
    public string $honor;

    /** @var GroupMember The group member. */
    public GroupMember $member;
}
