<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Message;

use DiceRobot\Data\Report\Contact\Stranger;
use DiceRobot\Data\Report\Message;

/**
 * Class StrangerMessage
 *
 * DTO. Stranger message report.
 *
 * @package DiceRobot\Data\Report\Message
 */
final class StrangerMessage extends Message
{
    /** @var Stranger Message sender. */
    public Stranger $sender;
}
