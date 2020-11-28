<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Event;

/**
 * Class BotInvitedJoinGroupRequestEvent
 *
 * DTO. Request event of that robot is invited to join the group.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#bot%E8%A2%AB%E9%82%80%E8%AF%B7%E5%85%A5%E7%BE%A4%E7%94%B3%E8%AF%B7
 */
final class BotInvitedJoinGroupRequestEvent extends Event
{
    /** @var int ID of the event. */
    public int $eventId;

    /** @var int ID of the requester. */
    public int $fromId;

    /** @var int Requested group's ID. */
    public int $groupId;

    /** @var string Requested group's name. */
    public string $groupName;

    /** @var string Nickname of the requester. */
    public string $nick;

    /** @var string Request message. */
    public string $message;
}
