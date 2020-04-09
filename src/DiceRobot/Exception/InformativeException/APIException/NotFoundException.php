<?php
namespace DiceRobot\Exception\InformativeException\APIException;

use DiceRobot\Exception\InformativeException;

/**
 * Character card not found. This exception will send reply "characterCardNotFound".
 */
final class NotFoundException extends InformativeException
{
    public function __construct()
    {
        parent::__construct("characterCardNotFound");
    }
}
