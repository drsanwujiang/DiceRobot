<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Event;

/**
 * Class BotOfflineEventDropped
 *
 * DTO. Event of that bot is dropped (goes offline passively) caused by network problem.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#bot%E8%A2%AB%E6%9C%8D%E5%8A%A1%E5%99%A8%E6%96%AD%E5%BC%80%E6%88%96%E5%9B%A0%E7%BD%91%E7%BB%9C%E9%97%AE%E9%A2%98%E8%80%8C%E6%8E%89%E7%BA%BF
 */
final class BotOfflineEventDropped extends Event
{
    /** @var int Bot ID. */
    public int $qq;
}
