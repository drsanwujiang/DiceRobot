<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\Group;
use DiceRobot\Data\Report\Event;

/**
 * Class BotLeaveEventKick
 *
 * DTO. Event of that robot is kicked out of a group.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class BotLeaveEventKick extends Event
{
    /** @var Group The group */
    public Group $group;
}
