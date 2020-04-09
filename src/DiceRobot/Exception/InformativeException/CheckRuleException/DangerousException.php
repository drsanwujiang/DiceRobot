<?php
namespace DiceRobot\Exception\InformativeException\CheckRuleException;

use DiceRobot\Exception\InformativeException;

/**
 * Check rule is dangerous. This exception will send reply "checkRuleDangerous".
 */
final class DangerousException extends InformativeException
{
    public function __construct()
    {
        parent::__construct("checkRuleDangerous");
    }
}
