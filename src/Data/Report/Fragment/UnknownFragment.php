<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

use DiceRobot\Interfaces\Fragment;

/**
 * Class UnknownFragment
 *
 * DTO. Unknown fragment.
 *
 * This is not a Mirai message fragment, only an abstraction.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class UnknownFragment implements Fragment
{
    /**
     * @inheritDoc
     *
     * @return array Message
     */
    public function toMessage(): array
    {
        return [
            "type" => "Unknown"
        ];
    }
}
