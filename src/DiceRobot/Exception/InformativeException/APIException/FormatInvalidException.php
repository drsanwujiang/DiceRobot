<?php
namespace DiceRobot\Exception\InformativeException\APIException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * Character card format invalid. This exception will send reply "characterCardFormatInvalid".
 */
class FormatInvalidException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("characterCardFormatInvalid"));
    }
}
