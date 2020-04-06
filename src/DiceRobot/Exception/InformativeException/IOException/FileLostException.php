<?php
namespace DiceRobot\Exception\InformativeException\IOException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * File is lost. This exception will send reply "IOFileLost".
 */
class FileLostException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("IOFileLost"));
    }
}
