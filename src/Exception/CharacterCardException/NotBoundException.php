<?php

declare(strict_types=1);

namespace DiceRobot\Exception\CharacterCardException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class NotBoundException
 *
 * Character card is not bound.
 *
 * @reply characterCardNotBound
 *
 * @package DiceRobot\Exception\CharacterCardException
 */
final class NotBoundException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("characterCardNotBound");
    }
}
