<?php
namespace DiceRobot\Exception;

use Exception;

/**
 * Informative exception. When this exception thrown, reply of the action will be replaced with specific reply.
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
