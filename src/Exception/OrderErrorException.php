<?php

declare(strict_types=1);

namespace DiceRobot\Exception;

/**
 * Class OrderErrorException
 *
 * Failed to parse the order.
 *
 * @reply _generalOrderError
 *
 * @package DiceRobot\Exception
 */
final class OrderErrorException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("_generalOrderError");
    }
}
