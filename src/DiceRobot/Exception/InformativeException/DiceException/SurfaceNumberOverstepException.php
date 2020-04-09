<?php
namespace DiceRobot\Exception\InformativeException\DiceException;

use DiceRobot\Exception\InformativeException;

/**
 * Dice surface number oversteps the limit. This exception will send reply "diceSurfaceNumberOverstep".
 */
final class SurfaceNumberOverstepException extends InformativeException
{
    public function __construct()
    {
        parent::__construct("diceSurfaceNumberOverstep");
    }
}
