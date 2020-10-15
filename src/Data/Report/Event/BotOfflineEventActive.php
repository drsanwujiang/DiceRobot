<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Event;

/**
 * Class BotOfflineEventActive
 *
 * DTO. Event of that robot logs out (go offline actively).
 *
 * @package DiceRobot\Data\Report\Event
 */
final class BotOfflineEventActive extends Event
{
    /** @var int Robot ID */
    public int $qq;
}
