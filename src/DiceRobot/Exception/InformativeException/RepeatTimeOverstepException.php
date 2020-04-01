<?php
namespace DiceRobot\Exception\InformativeException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * Repeat time oversteps the limit. This exception will send reply "_generalRepeatTimeOverstep".
 */
final class RepeatTimeOverstepException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("_generalRepeatTimeOverstep"));
    }
}
