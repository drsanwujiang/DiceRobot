<?php
namespace DiceRobot\Exception\InformativeException\APIException;

use DiceRobot\Exception\InformativeException;

/**
 * Unexpected code returned. This exception will send reply "APIUnexpectedError" (if event type is message), and
 * log the details into error log.
 */
final class UnexpectedErrorException extends InformativeException
{
    public function __construct(int $code, string $message, string $class)
    {
        parent::__construct("APIUnexpectedError");

        error_log("API server returned unexpected code [{$code}]: {$message}. Class: {$class}.");
    }
}
