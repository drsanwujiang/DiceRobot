<?php
namespace DiceRobot\Exception\CharacterCardException;

use DiceRobot\Base\Customization;
use DiceRobot\Exception\InformativeException;

/**
 * Character card file is lost. This exception will send reply "_generalCharacterCardLost".
 */
final class CharacterCardLostException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getCustomReply("_generalCharacterCardLost"));
    }
}
