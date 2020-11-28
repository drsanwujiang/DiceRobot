<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\{GroupMember, Operator};
use DiceRobot\Data\Report\Event;

/**
 * Class MemberCardChangeEvent
 *
 * DTO. Event of that a group member's card has changed.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#%E7%BE%A4%E5%90%8D%E7%89%87%E6%94%B9%E5%8A%A8
 */
final class MemberCardChangeEvent extends Event
{
    /** @var string Original card. */
    public string $origin;

    /** @var string Current card. */
    public string $current;

    /** @var GroupMember The group member. */
    public GroupMember $member;

    /** @var Operator|null The operator, null if the operator is the robot. */
    public ?Operator $operator;
}
