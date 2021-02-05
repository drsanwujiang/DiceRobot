<?php

declare(strict_types=1);

namespace DiceRobot\Exception;

/**
 * Class RepeatOverstepException
 *
 * Count of repetition oversteps the limit.
 *
 * @errorMessage _generalRepeatOverstep.
 *
 * @package DiceRobot\Exception
 */
final class RepeatOverstepException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("_generalRepeatOverstep");
    }
}
