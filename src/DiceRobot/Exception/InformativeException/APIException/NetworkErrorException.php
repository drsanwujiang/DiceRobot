<?php
namespace DiceRobot\Exception\InformativeException\APIException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * Network error occurred in cURL request. This exception will send reply "APINetworkError".
 */
class NetworkErrorException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("APINetworkError"));
    }
}
