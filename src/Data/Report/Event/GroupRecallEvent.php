<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\{Group, Operator};
use DiceRobot\Data\Report\Event;

/**
 * Class GroupRecallEvent
 *
 * DTO. Event of that a message is recalled in the group.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#%E7%BE%A4%E6%B6%88%E6%81%AF%E6%92%A4%E5%9B%9E
 */
final class GroupRecallEvent extends Event
{
    /** @var int Message sender's ID. */
    public int $authorId;

    /** @var int ID of the message. */
    public int $messageId;

    /** @var int The time when the message was sent. */
    public int $time;

    /** @var Group The group. */
    public Group $group;

    /** @var Operator|null The operator, null if the operator is the robot. */
    public ?Operator $operator;
}
