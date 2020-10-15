<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Event;

/**
 * Class BotOfflineEventForce
 *
 * DTO. Event of that robot log out forcedly (go offline forcedly) caused by another client's login.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class BotOfflineEventForce extends Event
{
    /** @var int Robot ID */
    public int $qq;
}
