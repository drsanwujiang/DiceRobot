<?php

declare(strict_types=1);

namespace DiceRobot\Exception\CharacterCardException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class NotFoundException
 *
 * Character card cannot be found.
 *
 * @errorMessage characterCardNotFound
 *
 * @package DiceRobot\Exception\CharacterCardException
 */
final class NotFoundException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("characterCardNotFound");
    }
}
