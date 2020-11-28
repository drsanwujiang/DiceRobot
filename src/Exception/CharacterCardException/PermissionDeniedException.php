<?php

declare(strict_types=1);

namespace DiceRobot\Exception\CharacterCardException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class PermissionDeniedException
 *
 * Character card cannot be accessed.
 *
 * @errorMessage characterCardPermissionDenied
 *
 * @package DiceRobot\Exception\CharacterCardException
 */
final class PermissionDeniedException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("characterCardPermissionDenied");
    }
}
