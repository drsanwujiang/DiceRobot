<?php
namespace DiceRobot\Exception\CharacterCardException;

use DiceRobot\Base\Customization;
use DiceRobot\Exception\InformativeException;

/**
 * Character card not bound. This exception will send reply "_generalCharacterCardNotBound".
 */
final class CharacterCardNotBoundException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getCustomReply("_generalCharacterCardNotBound"));
    }
}
