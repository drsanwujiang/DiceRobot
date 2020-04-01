<?php
namespace DiceRobot\Exception\InformativeException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * File is lost. This exception will send reply "_generalFileLost".
 */
final class FileLostException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("_generalFileLost"));
    }
}
