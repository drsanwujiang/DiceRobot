<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Message;

use DiceRobot\Data\Report\Contact\GroupSender;
use DiceRobot\Data\Report\Message;

/**
 * Class TempMessage
 *
 * DTO. Temp message report.
 *
 * @package DiceRobot\Data\Report\Message
 */
final class TempMessage extends Message
{
    /** @var GroupSender Message sender */
    public GroupSender $sender;
}
