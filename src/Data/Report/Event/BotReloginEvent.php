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
 */
final class BotReloginEvent extends Event
{
    /** @var int Robot ID */
    public int $qq;
}
