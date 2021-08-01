<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Event;

/**
 * Class FriendRecallEvent
 *
 * DTO. Event of that a friend recalled the message.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class FriendRecallEvent extends Event
{
    /** @var int Message sender ID. */
    public int $authorId;

    /** @var int Message ID. */
    public int $messageId;

    /** @var int The time when the message was sent. */
    public int $time;

    /** @var int Operator ID (friend or robot). */
    public int $operator;
}
