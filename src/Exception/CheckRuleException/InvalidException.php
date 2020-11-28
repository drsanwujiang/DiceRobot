<?php

declare(strict_types=1);

namespace DiceRobot\Exception\CheckRuleException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class InvalidException
 *
 * Check rule is invalid.
 *
 * @errorMessage checkRuleInvalid
 *
 * @package DiceRobot\Exception\CheckRuleException
 */
final class InvalidException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("checkRuleInvalid");
    }
}
