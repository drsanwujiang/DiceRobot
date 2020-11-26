<?php

declare(strict_types=1);

namespace DiceRobot\Exception\CardDeckException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class InvalidException
 *
 * Card deck is invalid.
 *
 * @reply cardDeckInvalid
 *
 * @package DiceRobot\Exception\CardDeckException
 */
class InvalidException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("cardDeckInvalid");
    }
}
