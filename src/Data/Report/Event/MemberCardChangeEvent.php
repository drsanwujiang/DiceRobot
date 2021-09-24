<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\GroupMember;
use DiceRobot\Data\Report\Event;

/**
 * Class MemberCardChangeEvent
 *
 * DTO. Event of that a group member's card changed.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class MemberCardChangeEvent extends Event
{
    /** @var string Original card. */
    public string $origin;

    /** @var string Current card. */
    public string $current;

    /** @var GroupMember The group member. */
    public GroupMember $member;

    /** @var GroupMember|null The operator, null if the operator is the robot. */
    public ?GroupMember $operator;
}
