<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Event;

/**
 * Class BotOfflineEventForce
 *
 * DTO. Event of that robot logs out forcedly (goes offline forcedly) caused by another client's login.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#bot%E8%A2%AB%E6%8C%A4%E4%B8%8B%E7%BA%BF
 */
final class BotOfflineEventForce extends Event
{
    /** @var int Robot's ID. */
    public int $qq;
}
