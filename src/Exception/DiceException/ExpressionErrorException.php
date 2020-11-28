<?php

declare(strict_types=1);

namespace DiceRobot\Exception\DiceException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class ExpressionErrorException
 *
 * Arithmetic expression is not evaluated correctly. This exception contains the details.
 *
 * @errorMessage diceExpressionError
 *
 * @package DiceRobot\Exception\DiceException
 */
final class ExpressionErrorException extends DiceRobotException
{
    /**
     * @inheritDoc
     *
     * @param string $errMsg Error message.
     * @param string $order Dicing order.
     * @param string $expression Parsed expression.
     * @param string $arithmeticExpression Parsed arithmetic expression.
     */
    public function __construct(string $errMsg, string $order, string $expression, string $arithmeticExpression)
    {
        $extraMessage =
            "DiceRobot catch an arithmetic expression error: {$errMsg}, " .
            "dicing order: {$order}, " .
            "parsed expression: {$expression}, " .
            "parsed arithmetic expression: {$arithmeticExpression}";

        parent::__construct("diceExpressionError", $extraMessage);
    }
}
