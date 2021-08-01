<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\Friend;
use DiceRobot\Data\Report\Event;

/**
 * Class FriendInputStatusChangedEvent
 *
 * DTO. Event of that a friend's input status changed.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class FriendInputStatusChangedEvent extends Event
{
    /** @var Friend The friend. */
    public Friend $friend;

    /** @var bool Current input status. */
    public bool $inputting;
}
