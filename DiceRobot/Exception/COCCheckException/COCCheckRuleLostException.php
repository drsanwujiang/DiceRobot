<?php
namespace DiceRobot\Exception\COCCheckException;

use DiceRobot\Base\Customization;
use DiceRobot\Exception\InformativeException;

/**
 * Class COCCheckRuleLostException
 *
 * Exception thrown when COC check rule is lost. This exception will send reply "checkDiceRuleLost".
 */
final class COCCheckRuleLostException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getCustomReply("checkDiceRuleLost"));
    }
}
