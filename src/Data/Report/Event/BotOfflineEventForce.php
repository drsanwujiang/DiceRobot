<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Event;

/**
 * Class BotOfflineEventForce
 *
 * DTO. Event of that bot logs out forcedly (goes offline forcedly) caused by another client's login.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class BotOfflineEventForce extends Event
{
    /** @var int Bot ID. */
    public int $qq;
}
