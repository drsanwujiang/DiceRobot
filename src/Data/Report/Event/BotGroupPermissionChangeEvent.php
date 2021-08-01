<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\Group;
use DiceRobot\Data\Report\Event;

/**
 * Class BotGroupPermissionChangeEvent
 *
 * DTO. Event of that robot's permission in the group changed.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class BotGroupPermissionChangeEvent extends Event
{
    /** @var string Original permission. */
    public string $origin;

    /** @var string Current permission. */
    public string $current;

    /** @var Group The group. */
    public Group $group;
}
