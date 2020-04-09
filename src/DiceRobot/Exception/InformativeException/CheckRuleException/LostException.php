<?php
namespace DiceRobot\Exception\InformativeException\CheckRuleException;

use DiceRobot\Exception\InformativeException;

/**
 * Check rule is lost. This exception will send reply "checkRuleLost".
 */
final class LostException extends InformativeException
{
    public function __construct()
    {
        parent::__construct("checkRuleLost");
    }
}