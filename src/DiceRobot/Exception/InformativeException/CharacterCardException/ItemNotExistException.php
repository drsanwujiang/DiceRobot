<?php
namespace DiceRobot\Exception\InformativeException\CharacterCardException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * Item does not exist in character card. This exception will send reply "characterCardItemNotExist".
 */
class ItemNotExistException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("characterCardItemNotExist"));
    }
}
