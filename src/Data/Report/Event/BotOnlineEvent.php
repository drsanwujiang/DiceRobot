<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Event;

/**
 * Class BotOnlineEvent
 *
 * DTO. Event of that bot successfully logins.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class BotOnlineEvent extends Event
{
    /** @var int Bot ID. */
    public int $qq;
}
