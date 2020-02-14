<?php
namespace DiceRobot\Exception;

use Exception;

/**
 * Class InformativeException
 *
 * Parent class to all the InformativeException. When this exception thrown, reply of action will be replaced with
 * specific reply.
 */
abstract class InformativeException extends Exception
{
    protected string $reply;

    public function __construct(string $reply)
    {
        parent::__construct();

        $this->reply = $reply;
    }

    public function __toString()
    {
        return $this->reply;
    }
}
