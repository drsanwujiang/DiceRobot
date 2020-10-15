<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\Group;
use DiceRobot\Data\Report\Event;

/**
 * Class BotJoinGroupEvent
 *
 * DTO. Event of that robot joins a group.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class BotJoinGroupEvent extends Event
{
    /** @var Group The group */
    public Group $group;
}
