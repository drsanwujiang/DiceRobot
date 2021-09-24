<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Event;

/**
 * Class NudgeEvent
 *
 * DTO. Event of that a friend/group member's avatar was poked.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class NudgeEvent extends Event
{
    /** @var int Sender ID. */
    public int $fromId;

    /** @var object Event source (chat ID and type). */
    public object $subject;

    /** @var string Action type. */
    public string $action;

    /** @var string Action suffix */
    public string $suffix;

    /** @var int Action target ID. */
    public int $target;
}
