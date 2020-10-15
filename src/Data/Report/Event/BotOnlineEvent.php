<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Event;

/**
 * Class BotOnlineEvent
 *
 * DTO. Event of that robot successfully logs in.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class BotOnlineEvent extends Event
{
    /** @var int Robot ID */
    public int $qq;
}
