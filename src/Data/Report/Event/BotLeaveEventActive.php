<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\Group;
use DiceRobot\Data\Report\Event;

/**
 * Class BotLeaveEventActive
 *
 * DTO. Event of that robot has left the group actively.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#bot%E4%B8%BB%E5%8A%A8%E9%80%80%E5%87%BA%E4%B8%80%E4%B8%AA%E7%BE%A4
 */
final class BotLeaveEventActive extends Event
{
    /** @var Group The group. */
    public Group $group;
}
