<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\Group;
use DiceRobot\Data\Report\Event;

/**
 * Class BotGroupPermissionChangeEvent
 *
 * DTO. Event of that robot's permission in the group has changed.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#bot%E5%9C%A8%E7%BE%A4%E9%87%8C%E7%9A%84%E6%9D%83%E9%99%90%E8%A2%AB%E6%94%B9%E5%8F%98-%E6%93%8D%E4%BD%9C%E4%BA%BA%E4%B8%80%E5%AE%9A%E6%98%AF%E7%BE%A4%E4%B8%BB
 */
final class BotGroupPermissionChangeEvent extends Event
{
    /** @var string Original permission. */
    public string $origin;

    /** @var string Current permission. */
    public string $current;

    /** @var Group The group. */
    public Group $group;
}
