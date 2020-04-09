<?php
namespace DiceRobot\Exception\InformativeException\APIException;

use DiceRobot\Exception\InformativeException;

/**
 * Character card can not be accessed. This exception will send reply "characterCardPermissionDenied".
 */
final class PermissionDeniedException extends InformativeException
{
    public function __construct()
    {
        parent::__construct("characterCardPermissionDenied");
    }
}
