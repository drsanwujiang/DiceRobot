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
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#%E7%94%A8%E6%88%B7%E5%85%A5%E7%BE%A4%E7%94%B3%E8%AF%B7bot%E9%9C%80%E8%A6%81%E6%9C%89%E7%AE%A1%E7%90%86%E5%91%98%E6%9D%83%E9%99%90
 */
final class MemberJoinRequestEvent extends Event
{
    /** @var int Event ID. */
    public int $eventId;

    /** @var int Requester ID. */
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
