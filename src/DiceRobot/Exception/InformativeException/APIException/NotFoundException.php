<?php
namespace DiceRobot\Exception\InformativeException\APIException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * Character card not found. This exception will send reply "_generalCharacterCardNotFound".
 */
final class NotFoundException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("_generalCharacterCardNotFound"));
    }
}
