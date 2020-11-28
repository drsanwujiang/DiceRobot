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
    /** @var int Robot's ID. */
    public int $id;

    /** @var string Robot's nickname. */
    public string $nickname;

    /** @var string Authorization key of Mirai API HTTP plugin. */
    public string $authKey;
}
