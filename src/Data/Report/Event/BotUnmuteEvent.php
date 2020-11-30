<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\Operator;
use DiceRobot\Data\Report\Event;

/**
 * Class BotUnmuteEvent
 *
 * DTO. Event of that robot is unmuted in the group.
 *
 * @package DiceRobot\Data\Report\Event
 *
 * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#bot%E8%A2%AB%E5%8F%96%E6%B6%88%E7%A6%81%E8%A8%80
 */
final class BotUnmuteEvent extends Event
{
    /** @var Operator The operator. */
    public Operator $operator;
}
