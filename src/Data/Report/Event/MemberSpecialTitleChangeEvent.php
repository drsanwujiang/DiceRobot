<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\Member;
use DiceRobot\Data\Report\Event;

/**
 * Class MemberSpecialTitleChangeEvent
 *
 * DTO. Event of that a group member's special title changed.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class MemberSpecialTitleChangeEvent extends Event
{
    /** @var string Original special title. */
    public string $origin;

    /** @var string Current special title. */
    public string $current;

    /** @var Member The group member. */
    public Member $member;
}
