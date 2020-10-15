<?php

declare(strict_types=1);

namespace DiceRobot\Exception\ApiException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class UnexpectedErrorException
 *
 * DiceRobot API server returned unexpected code, but HTTP status code is acceptable (2xx).
 *
 * @reply apiUnexpectedError
 *
 * @package DiceRobot\Exception\ApiException
 */
final class UnexpectedErrorException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("apiUnexpectedError");
    }
}
