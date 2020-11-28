<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Event;

/**
 * Class BotReloginEvent
 *
 * DTO. Event of that robot successfully relogin.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#bot%E4%B8%BB%E5%8A%A8%E9%87%8D%E6%96%B0%E7%99%BB%E5%BD%95
 */
final class BotReloginEvent extends Event
{
    /** @var int Robot's ID. */
    public int $qq;
}
