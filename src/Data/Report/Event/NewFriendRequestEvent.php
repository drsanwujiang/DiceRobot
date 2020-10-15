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
 */
final class NewFriendRequestEvent extends Event
{
    /** @var int Event ID */
    public int $eventId;

    /** @var int Requester ID */
    public int $fromId;

    /** @var int Group ID */
    public int $groupId;

    /** @var string Requester nickname */
    public string $nick;

    /** @var string Request message */
    public string $message;
}
