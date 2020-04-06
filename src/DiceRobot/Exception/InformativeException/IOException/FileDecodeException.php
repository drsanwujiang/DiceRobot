<?php
namespace DiceRobot\Exception\InformativeException\IOException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * Failed to decode JSON string from file. This exception will send reply "IOFileDecodeError".
 */
class FileDecodeException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("IOFileDecodeError"));
    }
}
