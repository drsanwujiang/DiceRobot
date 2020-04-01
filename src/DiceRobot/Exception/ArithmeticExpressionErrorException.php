<?php
namespace DiceRobot\Exception;

use Exception;

/**
 * Arithmetic expression error exception thrown when arithmetic expression is not evaluated correctly.
 */
final class ArithmeticExpressionErrorException extends Exception
{
    public function __construct(string $errMessage, string $order, string $expression, string $arithmeticExpression)
    {
        $errMessage =
            "DiceRobot catch an arithmetic expression error: " . $errMessage . "\n" .
            "Exceptional rolling order: " . $order . "\n" .
            "Exceptional rolling expression: " . $expression . "\n" .
            "Exceptional command evaluated: " . $arithmeticExpression;

        parent::__construct($errMessage);
    }
}
