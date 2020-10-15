<?php

declare(strict_types=1);

namespace DiceRobot\Exception\CharacterCardException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class LostException
 *
 * Character card does not exist.
 *
 * @reply characterCardLost
 *
 * @package DiceRobot\Exception\CharacterCardException
 */
final class LostException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("characterCardLost");
    }
}
