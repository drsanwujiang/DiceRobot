<?php

declare(strict_types=1);

namespace DiceRobot\Interfaces;

/**
 * Interface Fragment
 *
 * Describe a fragment (aka single message) of Mirai message chain.
 *
 * @package DiceRobot\Interfaces
 */
interface Fragment
{
    /**
     * Convert fragment to single message of Mirai message chain.
     *
     * @return array Message.
     */
    public function toMessage(): array;
}
