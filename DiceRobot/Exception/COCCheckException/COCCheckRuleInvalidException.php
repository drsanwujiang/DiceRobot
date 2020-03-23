<?php
namespace DiceRobot\Exception\COCCheckException;

use DiceRobot\Base\Customization;
use DiceRobot\Exception\InformativeException;

/**
 * COC check rule is invalid. This exception will send reply "checkDiceRuleInvalid".
 */
final class COCCheckRuleInvalidException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getCustomReply("checkDiceRuleInvalid"));
    }
}
