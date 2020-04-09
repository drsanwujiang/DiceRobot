<?php
namespace DiceRobot\Exception\InformativeException\CharacterCardException;

use DiceRobot\Exception\InformativeException;

/**
 * Item does not exist in character card. This exception will send reply "characterCardItemNotExist".
 */
final class ItemNotExistException extends InformativeException
{
    public function __construct()
    {
        parent::__construct("characterCardItemNotExist");
    }
}
