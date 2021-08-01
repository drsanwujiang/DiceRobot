<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\Group;
use DiceRobot\Data\Report\Event;

/**
 * Class BotLeaveEventActive
 *
 * DTO. Event of that robot left the group actively.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class BotLeaveEventActive extends Event
{
    /** @var Group The group. */
    public Group $group;
}
