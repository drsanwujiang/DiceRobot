<?php

declare(strict_types=1);

namespace DiceRobot\Exception\CheckRuleException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class LostException
 *
 * Check rule is lost.
 *
 * @errorMessage checkRuleLost
 *
 * @package DiceRobot\Exception\CheckRuleException
 */
final class LostException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("checkRuleLost");
    }
}
