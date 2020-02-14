<?php
namespace DiceRobot\Exception\COCCheckException;

use DiceRobot\Base\Customization;
use DiceRobot\Exception\InformativeException;

/**
 * Class COCCheckRuleDangerousException
 *
 * Exception thrown when COC check rule is dangerous. This exception will send reply "checkDiceRuleDangerous".
 */
final class COCCheckRuleDangerousException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getCustomReply("checkDiceRuleDangerous"));
    }
}
