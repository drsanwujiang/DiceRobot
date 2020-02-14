<?php
namespace DiceRobot\Exception\COCCheckException;

use DiceRobot\Base\Customization;
use DiceRobot\Exception\InformativeException;

/**
 * Class COCCheckRuleMatchFailedException
 *
 * Exception thrown when failed to match COC check rule. This exception will send reply "checkDiceRuleMatchFailed".
 */
class COCCheckRuleMatchFailedException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getCustomReply("checkDiceRuleMatchFailed"));
    }
}
