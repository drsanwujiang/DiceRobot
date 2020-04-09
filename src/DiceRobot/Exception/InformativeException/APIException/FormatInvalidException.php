<?php
namespace DiceRobot\Exception\InformativeException\APIException;

use DiceRobot\Exception\InformativeException;

/**
 * Character card format invalid. This exception will send reply "characterCardFormatInvalid".
 */
final class FormatInvalidException extends InformativeException
{
    public function __construct()
    {
        parent::__construct("characterCardFormatInvalid");
    }
}
