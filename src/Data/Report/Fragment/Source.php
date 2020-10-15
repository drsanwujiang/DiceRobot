<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

use DiceRobot\Interfaces\Fragment;

/**
 * Class Source
 *
 * DTO. Source fragment.
 *
 * Source fragment is not familiar as other fragment, for it identify a message. So it will always be the first
 * element of message chain.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class Source implements Fragment
{
    /** @var int Message ID */
    public int $id;

    /** @var int Timestamp */
    public int $time;

    /**
     * @inheritDoc
     *
     * @return array Message
     */
    public function toMessage(): array
    {
        return [
            "type" => "Source",
            "id" => $this->id,
            "time" => $this->time
        ];
    }
}
