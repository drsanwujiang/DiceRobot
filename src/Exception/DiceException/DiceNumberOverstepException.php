<?php

declare(strict_types=1);

namespace DiceRobot\Exception\DiceException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class DiceNumberOverstepException
 *
 * Dice number oversteps the limit.
 *
 * @errorMessage diceNumberOverstep
 *
 * @package DiceRobot\Exception\DiceException
 */
final class DiceNumberOverstepException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("diceNumberOverstep");
    }
}
