<?php
namespace DiceRobot\Exception;

use DiceRobot\Base\Customization;

/**
 * Class CharacterCardLostException
 *
 * Exception thrown when character card file is lost. This exception will send reply "characterCardLost".
 */
final class CharacterCardLostException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getCustomReply("characterCardLost"));
    }
}
