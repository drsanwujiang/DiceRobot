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
 */
final class BotOfflineEventDropped extends Event
{
    /** @var int Bot ID. */
    public int $qq;
}
