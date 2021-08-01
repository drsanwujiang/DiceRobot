<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Event;

/**
 * Class BotOfflineEventActive
 *
 * DTO. Event of that bot logs out (goes offline actively).
 *
 * @package DiceRobot\Data\Report\Event
 */
final class BotOfflineEventActive extends Event
{
    /** @var int Bot ID. */
    public int $qq;
}
