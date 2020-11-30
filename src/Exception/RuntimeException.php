<?php

declare(strict_types=1);

namespace DiceRobot\Exception;

/**
 * Class RuntimeException
 *
 * This exception indicates that some functions did not perform as expected, and should be thrown with a message which
 * can be logged. Then this exception should be caught but NOT passed to application.
 *
 * @package DiceRobot\Exception
 */
final class RuntimeException extends \RuntimeException
{
    /**
     * Return error message.
     *
     * @return string Error message.
     */
    public function __toString(): string
    {
        return $this->getMessage();
    }
}
