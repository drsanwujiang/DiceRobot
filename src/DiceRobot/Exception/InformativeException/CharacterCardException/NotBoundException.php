<?php
namespace DiceRobot\Exception\InformativeException\CharacterCardException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * Character card not bound. This exception will send reply "_generalCharacterCardNotBound".
 */
final class NotBoundException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("_generalCharacterCardNotBound"));
    }
}
