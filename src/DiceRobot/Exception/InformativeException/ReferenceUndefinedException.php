<?php
namespace DiceRobot\Exception\InformativeException;

use DiceRobot\Exception\InformativeException;

/**
 * Reference file is undefined. This exception will send reply "_generalReferenceUndefined".
 */
final class ReferenceUndefinedException extends InformativeException
{
    public function __construct()
    {
        parent::__construct("_generalReferenceUndefined");
    }
}