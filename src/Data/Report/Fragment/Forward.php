<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

use DiceRobot\Interfaces\Fragment;

/**
 * Class Forward
 *
 * DTO. Forward message fragment.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class Forward implements Fragment
{
    /** @var MessageNode[] Message node list. */
    public array $nodeList;

    /**
     * @inheritDoc
     *
     * @return array Message.
     */
    public function toMessage(): array
    {
        return [
            "type" => "Forward",
            "nodeList" => $this->nodeList
        ];
    }
}
