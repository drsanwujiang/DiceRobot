<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\Operator;
use DiceRobot\Data\Report\Event;

/**
 * Class BotMuteEvent
 *
 * DTO. Event of that robot is muted in the group.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class BotMuteEvent extends Event
{
    /** @var int Muting duration. */
    public int $durationSeconds;

    /** @var Operator The operator. */
    public Operator $operator;
}
