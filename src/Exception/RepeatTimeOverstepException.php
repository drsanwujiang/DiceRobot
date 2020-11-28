<?php

declare(strict_types=1);

namespace DiceRobot\Exception;

/**
 * Class RepeatTimeOverstepException
 *
 * Repeat time oversteps the limit.
 *
 * @errorMessage _generalRepeatTimeOverstep.
 *
 * @package DiceRobot\Exception
 */
final class RepeatTimeOverstepException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("_generalRepeatTimeOverstep");
    }
}
