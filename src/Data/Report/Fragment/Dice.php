<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

use DiceRobot\Interfaces\Fragment;

/**
 * Class Dice
 *
 * DTO. Dice message fragment.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class Dice implements Fragment
{
    /** @var int Dice value. */
    public int $value;

    /**
     * @inheritDoc
     *
     * @return array Message.
     */
    public function toMessage(): array
    {
        return [
            "type" => "Dice",
            "value" => $this->value
        ];
    }
}
