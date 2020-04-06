<?php
namespace DiceRobot\Exception\InformativeException\DiceException;

use DiceRobot\Service\Customization;
use Exception;

/**
 * Arithmetic expression error exception thrown when arithmetic expression is not evaluated correctly. This exception
 * will send reply "diceExpressionError", and log the details into error log.
 */
class ExpressionErrorException extends Exception
{
    public function __construct(string $errMessage, string $order, string $expression, string $arithmeticExpression)
    {
        parent::__construct(Customization::getReply("diceExpressionError"));

        error_log(
            "DiceRobot catch an arithmetic expression error: {$errMessage}\n" .
            "Exceptional rolling order: {$order}\n" .
            "Exceptional rolling expression: {$expression}\n" .
            "Exceptional arithmetic expression: {$arithmeticExpression}"
        );
    }
}
