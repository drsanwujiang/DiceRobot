<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Message;

use DiceRobot\Data\Report\Contact\OtherClient;
use DiceRobot\Data\Report\Message;

/**
 * Class OtherClientMessage
 *
 * DTO. Other client message report.
 *
 * @package DiceRobot\Data\Report\Message
 */
final class OtherClientMessage extends Message
{
    /** @var OtherClient Message sender. */
    public OtherClient $sender;
}
