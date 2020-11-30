<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Event;

/**
 * Class NewFriendRequestEvent
 *
 * DTO. Event of that robot is requested to be a new friend.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#%E6%B7%BB%E5%8A%A0%E5%A5%BD%E5%8F%8B%E7%94%B3%E8%AF%B7
 */
final class NewFriendRequestEvent extends Event
{
    /** @var int ID of the event. */
    public int $eventId;

    /** @var int ID of the requester. */
    public int $fromId;

    /** @var int Requested group's ID. */
    public int $groupId;

    /** @var string Nickname of the requester. */
    public string $nick;

    /** @var string Request message. */
    public string $message;
}
