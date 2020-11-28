<?php

declare(strict_types=1);

namespace DiceRobot\Exception\FileException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class LostException
 *
 * Requested file is lost.
 *
 * @errorMessage fileLost
 *
 * @package DiceRobot\Exception\FileException
 */
final class LostException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("fileLost");
    }
}
