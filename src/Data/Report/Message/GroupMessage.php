<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Message;

use DiceRobot\Data\Report\Contact\GroupMember;
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
    /** @var GroupMember Message sender. */
    public GroupMember $sender;
}
