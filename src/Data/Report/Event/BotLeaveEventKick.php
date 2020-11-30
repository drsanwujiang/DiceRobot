<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\Group;
use DiceRobot\Data\Report\Event;

/**
 * Class BotLeaveEventKick
 *
 * DTO. Event of that robot is kicked out of the group.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#bot%E8%A2%AB%E8%B8%A2%E5%87%BA%E4%B8%80%E4%B8%AA%E7%BE%A4
 */
final class BotLeaveEventKick extends Event
{
    /** @var Group The group. */
    public Group $group;
}
