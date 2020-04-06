<?php
namespace DiceRobot\Exception\InformativeException\CheckRuleException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * Check rule is invalid. This exception will send reply "checkRuleInvalid".
 */
class InvalidException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("checkRuleInvalid"));
    }
}
