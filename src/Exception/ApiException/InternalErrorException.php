<?php

declare(strict_types=1);

namespace DiceRobot\Exception\ApiException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class InternalErrorException
 *
 * DiceRobot API server returned HTTP status code 4xx/5xx, which means the request is not correctly processed.
 *
 * @errorMessage apiInternalError
 *
 * @package DiceRobot\Exception\ApiException
 */
final class InternalErrorException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("apiInternalError");
    }
}
