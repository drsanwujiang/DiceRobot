<?php

declare(strict_types=1);

namespace DiceRobot\Exception;

use Exception;

/**
 * Class DiceRobotException
 *
 * Base exception of DiceRobot.
 *
 * All the DiceRobot exceptions, that show error message to the message sender, should extend this exception. Message
 * report handler will catch this exception and send the corresponding message. If exception contains extra message,
 * the message will be logged.
 *
 * @package DiceRobot\Exception
 */
abstract class DiceRobotException extends Exception
{
    /** @var string Error message key. */
    protected string $errMsgKey;

    /** @var string Extra message. */
    public string $extMsg;

    /**
     * The constructor.
     *
     * @param string $errMsgKey Error message key.
     * @param string $extMsg Extra message.
     */
    public function __construct(string $errMsgKey, string $extMsg = "")
    {
        parent::__construct();

        $this->errMsgKey = $errMsgKey;
        $this->extMsg = $extMsg;
    }

    /**
     * Return error message key.
     *
     * @return string Error message key.
     */
    public function __toString(): string
    {
        return $this->errMsgKey;
    }
}
