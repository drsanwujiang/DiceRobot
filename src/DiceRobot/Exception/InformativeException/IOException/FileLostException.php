<?php
namespace DiceRobot\Exception\InformativeException\IOException;

use DiceRobot\Exception\InformativeException;

/**
 * File is lost. This exception will send reply "IOFileLost".
 */
final class FileLostException extends InformativeException
{
    public function __construct()
    {
        parent::__construct("IOFileLost");
    }
}
