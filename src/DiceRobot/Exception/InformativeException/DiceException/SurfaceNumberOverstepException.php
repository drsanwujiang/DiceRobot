<?php
namespace DiceRobot\Exception\InformativeException\DiceException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * Dice surface number oversteps the limit. This exception will send reply "_generalDiceSurfaceNumberOverstep".
 */
final class SurfaceNumberOverstepException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("_generalDiceSurfaceNumberOverstep"));
    }
}
