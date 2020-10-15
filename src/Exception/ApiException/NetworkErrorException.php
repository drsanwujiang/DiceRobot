<?php

declare(strict_types=1);

namespace DiceRobot\Exception\ApiException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class NetworkErrorException
 *
 * Unable to connect DiceRobot API server.
 *
 * @reply apiNetworkError
 *
 * @package DiceRobot\Exception\ApiException
 */
final class NetworkErrorException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("apiNetworkError");
    }
}
