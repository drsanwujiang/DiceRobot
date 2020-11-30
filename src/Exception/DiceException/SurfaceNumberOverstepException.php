<?php

declare(strict_types=1);

namespace DiceRobot\Exception\DiceException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class SurfaceNumberOverstepException
 *
 * Dice surface number oversteps the limit.
 *
 * @errorMessage diceSurfaceNumberOverstep
 *
 * @package DiceRobot\Exception\DiceException
 */
final class SurfaceNumberOverstepException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("diceSurfaceNumberOverstep");
    }
}
