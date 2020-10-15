<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Event;

/**
 * Class BotOfflineEventDropped
 *
 * DTO. Event of that robot is dropped (go offline passively) caused by network problem.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class BotOfflineEventDropped extends Event
{
    /** @var int Robot ID */
    public int $qq;
}
