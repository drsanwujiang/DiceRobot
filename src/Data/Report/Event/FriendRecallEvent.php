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
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#%E5%A5%BD%E5%8F%8B%E6%B6%88%E6%81%AF%E6%92%A4%E5%9B%9E
 */
final class FriendRecallEvent extends Event
{
    /** @var int Message sender's ID. */
    public int $authorId;

    /** @var int Message ID. */
    public int $messageId;

    /** @var int The time when the message was sent. */
    public int $time;

    /** @var int Recall operator's ID (friend or robot). */
    public int $operator;
}
