<?php

declare(strict_types=1);

namespace DiceRobot\Exception\CardDeckException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class NotFoundException
 *
 * Card deck cannot be found.
 *
 * @reply cardDeckNotFound
 *
 * @package DiceRobot\Exception\CardDeckException
 */
class NotFoundException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("cardDeckNotFound");
    }
}
