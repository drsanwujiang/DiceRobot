<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Event;

use DiceRobot\Data\Report\Contact\OtherClient;
use DiceRobot\Data\Report\Event;

/**
 * Class OtherClientOnlineEvent
 *
 * DTO. Event of that other client logins.
 *
 * @package DiceRobot\Data\Report\Event
 */
final class OtherClientOnlineEvent extends Event
{
    /** @var OtherClient The client. */
    public OtherClient $client;

    /** @var int|null Client type. */
    public ?int $kind;
}
