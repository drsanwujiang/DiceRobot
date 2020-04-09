<?php
namespace DiceRobot\Exception\InformativeException\APIException;

use DiceRobot\Exception\InformativeException;

/**
 * Internal error occurred in API server. This exception will send reply "APIInternalError".
 */
final class InternalErrorException extends InformativeException
{
    public function __construct()
    {
        parent::__construct("APIInternalError");
    }
}
