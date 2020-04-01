<?php
namespace DiceRobot\Exception\InformativeException\CheckRuleException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * Failed to match check rule. This exception will send reply "checkRuleMatchFailed".
 */
final class MatchFailedException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("checkRuleMatchFailed"));
    }
}
