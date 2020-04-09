<?php
namespace DiceRobot\Exception\InformativeException;

use DiceRobot\Exception\InformativeException;

/**
 * Failed to parse the order. This exception will send reply "_generalOrderError".
 */
final class OrderErrorException extends InformativeException
{
    public function __construct()
    {
        parent::__construct("_generalOrderError");
    }
}
