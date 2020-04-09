<?php
namespace DiceRobot\Exception\InformativeException\IOException;

use DiceRobot\Exception\InformativeException;

/**
 * Failed to decode JSON string from file. This exception will send reply "IOFileDecodeError".
 */
final class FileDecodeException extends InformativeException
{
    public function __construct()
    {
        parent::__construct("IOFileDecodeError");
    }
}
