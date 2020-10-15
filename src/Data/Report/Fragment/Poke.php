<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

use DiceRobot\Interfaces\Fragment;

/**
 * Class Poke
 *
 * DTO. Poke message fragment.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class Poke implements Fragment
{
    /** @var string Poke name */
    public string $name;

    /**
     * @inheritDoc
     *
     * @return array Message
     */
    public function toMessage(): array
    {
        return [
            "type" => "Poke",
            "name" => $this->name
        ];
    }
}
