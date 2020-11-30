<?php

declare(strict_types=1);

namespace DiceRobot\Exception\DiceException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class ExpressionInvalidException
 *
 * Subexpression is invalid.
 *
 * @errorMessage diceExpressionInvalid
 *
 * @package DiceRobot\Exception\DiceException
 */
final class ExpressionInvalidException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("diceExpressionInvalid");
    }
}
