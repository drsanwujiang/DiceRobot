<?php

declare(strict_types=1);

namespace DiceRobot\Data\Contact;

/**
 * Class Bot
 *
 * DTO. Abstract Mirai Bot.
 *
 * @package DiceRobot\Data
 */
class Bot
{
    /** @var int Bot ID. */
    public int $id;

    /** @var string Mirai API HTTP plugin version. */
    public string $version;
}
