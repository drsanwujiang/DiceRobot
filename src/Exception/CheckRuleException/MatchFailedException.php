<?php

declare(strict_types=1);

namespace DiceRobot\Exception\CheckRuleException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class MatchFailedException
 *
 * Failed to match check rule.
 *
 * @reply checkRuleMatchFailed
 *
 * @package DiceRobot\Exception\CheckRuleException
 */
final class MatchFailedException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("checkRuleMatchFailed");
    }
}
