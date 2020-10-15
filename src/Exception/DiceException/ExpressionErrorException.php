<?php

declare(strict_types=1);

namespace DiceRobot\Exception\DiceException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class ExpressionErrorException
 *
 * Arithmetic expression is not evaluated correctly. This exception contains the details.
 *
 * @reply diceExpressionError
 *
 * @package DiceRobot\Exception\DiceException
 */
final class ExpressionErrorException extends DiceRobotException
{
    /**
     * @inheritDoc
     *
     * @param string $errMessage
     * @param string $order
     * @param string $expression
     * @param string $arithmeticExpression
     */
    public function __construct(string $errMessage, string $order, string $expression, string $arithmeticExpression)
    {
        $extraMessage =
            "DiceRobot catch an arithmetic expression error: {$errMessage}, " .
            "dicing order: {$order}, " .
            "parsed expression: {$expression}, " .
            "parsed arithmetic expression: {$arithmeticExpression}";

        parent::__construct("diceExpressionError", $extraMessage);
    }
}
