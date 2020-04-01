<?php
namespace DiceRobot\Exception\InformativeException;

use DiceRobot\Exception\InformativeException;
use DiceRobot\Service\Customization;

/**
 * File is unwritable. This exception will send reply "_generalFileUnwritable".
 */
final class FileUnwritableException extends InformativeException
{
    public function __construct()
    {
        parent::__construct(Customization::getReply("_generalFileUnwritable"));
    }
}
