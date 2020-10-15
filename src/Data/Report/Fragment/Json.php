<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

use DiceRobot\Interfaces\Fragment;

/**
 * Class Json
 *
 * DTO. JSON message fragment.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class Json implements Fragment
{
    /** @var string JSON string */
    public string $json;

    /**
     * @inheritDoc
     *
     * @return array Message
     */
    public function toMessage(): array
    {
        return [
            "type" => "Json",
            "json" => $this->json
        ];
    }
}
