<?php
namespace DiceRobot\Exception\InformativeException\APIException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * Internal error occurred in APIService server. This exception will send reply "_generalAPIInternalError".
 */
final class InternalErrorException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("_generalAPIInternalError"));
    }
}
