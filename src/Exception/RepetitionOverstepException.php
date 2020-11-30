<?php

declare(strict_types=1);

namespace DiceRobot\Exception;

/**
 * Class RepetitionOverstepException
 *
 * Count of repetition oversteps the limit.
 *
 * @errorMessage _generalRepetitionOverstep.
 *
 * @package DiceRobot\Exception
 */
final class RepetitionOverstepException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("_generalRepetitionOverstep");
    }
}
