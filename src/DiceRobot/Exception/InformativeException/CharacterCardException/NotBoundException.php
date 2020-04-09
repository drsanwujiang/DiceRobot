<?php
namespace DiceRobot\Exception\InformativeException\CharacterCardException;

use DiceRobot\Exception\InformativeException;

/**
 * Character card is not bound. This exception will send reply "characterCardNotBound".
 */
final class NotBoundException extends InformativeException
{
    public function __construct()
    {
        parent::__construct("characterCardNotBound");
    }
}
