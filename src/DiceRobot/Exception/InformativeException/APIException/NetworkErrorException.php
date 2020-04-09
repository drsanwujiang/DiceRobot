<?php
namespace DiceRobot\Exception\InformativeException\APIException;

use DiceRobot\Exception\InformativeException;

/**
 * Network error occurred in cURL request. This exception will send reply "APINetworkError".
 */
final class NetworkErrorException extends InformativeException
{
    public function __construct()
    {
        parent::__construct("APINetworkError");
    }
}
