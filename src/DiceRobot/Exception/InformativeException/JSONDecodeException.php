<?php
namespace DiceRobot\Exception\InformativeException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * Failed to decode JSON string from HTTP response. This exception will send reply "_generalJSONDecodeError".
 */
class JSONDecodeException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("_generalJSONDecodeError"));
    }
}
