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
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#bot%E8%A2%AB%E7%A6%81%E8%A8%80
 */
final class BotMuteEvent extends Event
{
    /** @var int Muting duration. */
    public int $durationSeconds;

    /** @var Operator The operator. */
    public Operator $operator;
}
