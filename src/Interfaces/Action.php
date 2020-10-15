<?php

declare(strict_types=1);

namespace DiceRobot\Interfaces;

/**
 * Interface Action
 *
 * Describe an action that can be executed to handle report.
 *
 * Action is the logic to handle report, so it is actually simple expansion of closure.
 *
 * @package DiceRobot\Interfaces
 */
interface Action
{
    /**
     * Main logic of the action.
     */
    public function __invoke(): void;
}
