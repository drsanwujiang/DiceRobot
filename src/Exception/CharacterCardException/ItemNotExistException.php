<?php

declare(strict_types=1);

namespace DiceRobot\Exception\CharacterCardException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class ItemNotExistException
 *
 * Item does not exist in the character card.
 *
 * @errorMessage characterCardItemNotExist
 *
 * @package DiceRobot\Exception\CharacterCardException
 */
final class ItemNotExistException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("characterCardItemNotExist");
    }
}
