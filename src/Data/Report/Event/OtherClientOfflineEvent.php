<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\OtherClient;
use DiceRobot\Data\Report\Event;

/**
 * Class OtherClientOfflineEvent
 *
 * DTO. Event of that other client logs out.
 *
 * @package DiceRobot\Data\Report\Event
 */
class OtherClientOfflineEvent extends Event
{
    /** @var OtherClient The client. */
    public OtherClient $client;
}
