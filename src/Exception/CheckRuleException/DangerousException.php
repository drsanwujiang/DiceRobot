<?php

declare(strict_types=1);

namespace DiceRobot\Exception\CheckRuleException;

use DiceRobot\Exception\DiceRobotException;

/**
 * Class DangerousException
 *
 * Check rule is dangerous.
 *
 * @errorMessage checkRuleDangerous
 *
 * @package DiceRobot\Exception\CheckRuleException
 */
final class DangerousException extends DiceRobotException
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct("checkRuleDangerous");
    }
}
