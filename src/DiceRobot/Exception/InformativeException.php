<?php
namespace DiceRobot\Exception;

use DiceRobot\Service\Customization;
use Exception;

/**
 * Informative exception. When this exception thrown, reply of the action will be replaced with specific reply.
 */
class InformativeException extends Exception
{
    protected string $reply;

    public function __construct(string $replyKey, ...$args)
    {
        parent::__construct();

        $this->reply = Customization::getReply($replyKey, ...$args);
    }

    public function __toString()
    {
        return $this->reply;
    }
}
