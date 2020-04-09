<?php
namespace DiceRobot\Exception\InformativeException\DiceException;

use DiceRobot\Exception\InformativeException;

/**
 * Dice number oversteps the limit. This exception will send reply "diceNumberOverstep".
 */
final class DiceNumberOverstepException extends InformativeException
{
    public function __construct()
    {
        parent::__construct("diceNumberOverstep");
    }
}
