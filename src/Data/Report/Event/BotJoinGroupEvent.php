<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\Group;
use DiceRobot\Data\Report\Event;

/**
 * Class BotJoinGroupEvent
 *
 * DTO. Event of that robot joined the group.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#bot%E5%8A%A0%E5%85%A5%E4%BA%86%E4%B8%80%E4%B8%AA%E6%96%B0%E7%BE%A4
 */
final class BotJoinGroupEvent extends Event
{
    /** @var Group The group. */
    public Group $group;
}
