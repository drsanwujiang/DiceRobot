<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Contact;

/**
 * Class OtherClient
 *
 * DTO. Other client.
 *
 * @package DiceRobot\Data\Report\Contact
 */
final class OtherClient extends Sender
{
    /** @var string Other client platform. */
    public string $platform;
}
