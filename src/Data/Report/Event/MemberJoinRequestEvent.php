<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Event;

/**
 * Class MemberJoinRequestEvent
 *
 * DTO. Event of that a new member requests to join the group.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class MemberJoinRequestEvent extends Event
{
    /** @var int Event ID. */
    public int $eventId;

    /** @var int Sender ID. */
    public int $fromId;

    /** @var int Target group ID. */
    public int $groupId;

    /** @var string Target group name. */
    public string $groupName;

    /** @var string Sender nickname. */
    public string $nick;

    /** @var string Request message. */
    public string $message;
}
