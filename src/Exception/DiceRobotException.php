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
 * @reply
 *
 * @package DiceRobot\Exception
 */
abstract class DiceRobotException extends Exception
{
    /** @var string Error message key */
    protected string $errorMessageKey;

    /** @var string Extra message */
    public string $extraMessage;

    /**
     * The constructor.
     *
     * @param string $errorMessageKey The error message key
     * @param string $extraMessage The extra message
     */
    public function __construct(string $errorMessageKey, string $extraMessage = "")
    {
        parent::__construct();

        $this->errorMessageKey = $errorMessageKey;
        $this->extraMessage = $extraMessage;
    }

    /**
     * Return error message key.
     *
     * @return string Error message key
     */
    public function __toString(): string
    {
        return $this->errorMessageKey;
    }
}
