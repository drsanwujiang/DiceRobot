<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Message;

use DiceRobot\Data\Report\Contact\Friend;
use DiceRobot\Data\Report\Message;

/**
 * Class FriendMessage
 *
 * DTO. Friend message report.
 *
 * @package DiceRobot\Data\Report\Message
 */
final class FriendMessage extends Message
{
    /** @var Friend Message sender. */
    public Friend $sender;
}
