<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Message;

use DiceRobot\Data\Report\Contact\FriendSender;
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
    /** @var FriendSender Message sender */
    public FriendSender $sender;
}
