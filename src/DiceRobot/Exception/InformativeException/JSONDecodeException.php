<?php
namespace DiceRobot\Exception\InformativeException;

use DiceRobot\Exception\InformativeException;

/**
 * Failed to decode JSON string from HTTP response. This exception will send reply "_generalJSONDecodeError".
 */
final class JSONDecodeException extends InformativeException
{
    public function __construct()
    {
        parent::__construct("_generalJSONDecodeError");
    }
}
