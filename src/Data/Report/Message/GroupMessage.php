<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Message;

use DiceRobot\Data\Report\Contact\GroupSender;
use DiceRobot\Data\Report\Message;

/**
 * Class GroupMessage
 *
 * DTO. Group message report.
 *
 * @package DiceRobot\Data\Report\Message
 */
final class GroupMessage extends Message
{
    /** @var GroupSender Message sender */
    public GroupSender $sender;
}
