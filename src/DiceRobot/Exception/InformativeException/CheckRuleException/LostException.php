<?php
namespace DiceRobot\Exception\InformativeException\CheckRuleException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * Check rule is lost. This exception will send reply "checkRuleLost".
 */
class LostException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("checkRuleLost"));
    }
}
