<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Event;

/**
 * Class BotOfflineEventActive
 *
 * DTO. Event of that robot logs out (goes offline actively).
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#bot%E7%99%BB%E5%BD%95%E6%88%90%E5%8A%9F
 */
final class BotOfflineEventActive extends Event
{
    /** @var int Robot's ID. */
    public int $qq;
}
