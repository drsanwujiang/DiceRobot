<?php
namespace DiceRobot\Exception\InformativeException\DiceException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * Dice number oversteps the limit. This exception will send reply "_generalDiceNumberOverstep".
 */
final class DiceNumberOverstepException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("_generalDiceNumberOverstep"));
    }
}
