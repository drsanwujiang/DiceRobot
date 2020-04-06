<?php
namespace DiceRobot\Exception\InformativeException\APIException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * Internal error occurred in API server. This exception will send reply "APIInternalError".
 */
class InternalErrorException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("APIInternalError"));
    }
}
