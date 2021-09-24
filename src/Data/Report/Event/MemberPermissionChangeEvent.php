<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\Member;
use DiceRobot\Data\Report\Event;

/**
 * Class MemberPermissionChangeEvent
 *
 * DTO. Event of that a group member's permission changed.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class MemberPermissionChangeEvent extends Event
{
    /** @var string Original permission. */
    public string $origin;

    /** @var string Current permission. */
    public string $current;

    /** @var Member The group member. */
    public Member $member;
}
