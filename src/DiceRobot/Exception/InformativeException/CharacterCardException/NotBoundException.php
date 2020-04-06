<?php
namespace DiceRobot\Exception\InformativeException\CharacterCardException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * Character card is not bound. This exception will send reply "characterCardNotBound".
 */
class NotBoundException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("characterCardNotBound"));
    }
}
