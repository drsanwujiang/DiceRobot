<?php
namespace DiceRobot\Exception\InformativeException;

use DiceRobot\Exception\InformativeException;

/**
 * Repeat time oversteps the limit. This exception will send reply "_generalRepeatTimeOverstep".
 */
final class RepeatTimeOverstepException extends InformativeException
{
    public function __construct()
    {
        parent::__construct("_generalRepeatTimeOverstep");
    }
}
