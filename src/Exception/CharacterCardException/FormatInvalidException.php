<?php

declare(strict_types=1);

namespace DiceRobot\Exception\CharacterCardException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class FormatInvalidException
 *
 * Character card format invalid.
 *
 * @reply characterCardFormatInvalid
 *
 * @package DiceRobot\Exception\CharacterCardException
 */
final class FormatInvalidException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("characterCardFormatInvalid");
    }
}
