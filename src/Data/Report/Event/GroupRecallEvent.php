<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\{Group, GroupMember};
use DiceRobot\Data\Report\Event;

/**
 * Class GroupRecallEvent
 *
 * DTO. Event of that a message is recalled in the group.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class GroupRecallEvent extends Event
{
    /** @var int Message sender's ID. */
    public int $authorId;

    /** @var int Message ID. */
    public int $messageId;

    /** @var int The time when the message was sent. */
    public int $time;

    /** @var Group The group. */
    public Group $group;

    /** @var GroupMember|null The operator, null if the operator is the robot. */
    public ?GroupMember $operator;
}
