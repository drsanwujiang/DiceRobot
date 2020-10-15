<?php

declare(strict_types=1);

namespace DiceRobot\Data\Contact;

/**
 * Class Robot
 *
 * DTO. Abstract Mirai Bot.
 *
 * @package DiceRobot\Data
 */
class Robot
{
    /** @var int Robot ID */
    public int $id;

    /** @var string Robot nickname */
    public string $nickname;

    /** @var string Auth key of Mirai API HTTP */
    public string $authKey;
}
