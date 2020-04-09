<?php
namespace DiceRobot\Exception\InformativeException\IOException;

use DiceRobot\Exception\InformativeException;

/**
 * File is unwritable. This exception will send reply "IOFileUnwritable".
 */
final class FileUnwritableException extends InformativeException
{
    public function __construct(string $errMessage)
    {
        parent::__construct("IOFileUnwritable");

        error_log($errMessage);
    }
}
