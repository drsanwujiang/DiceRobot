<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\Friend;
use DiceRobot\Data\Report\Event;

/**
 * Class FriendNickChangedEvent
 *
 * DTO. Event of that a friend's nickname changed.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class FriendNickChangedEvent extends Event
{
    /** @var Friend The friend. */
    public Friend $friend;

    /** @var string Previous nickname. */
    public string $from;

    /** @var string Current nickname. */
    public string $to;
}
