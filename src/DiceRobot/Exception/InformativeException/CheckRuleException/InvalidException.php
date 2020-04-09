<?php
namespace DiceRobot\Exception\InformativeException\CheckRuleException;

use DiceRobot\Exception\InformativeException;

/**
 * Check rule is invalid. This exception will send reply "checkRuleInvalid".
 */
final class InvalidException extends InformativeException
{
    public function __construct()
    {
        parent::__construct("checkRuleInvalid");
    }
}
