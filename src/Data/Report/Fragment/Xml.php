<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

use DiceRobot\Interfaces\Fragment;

/**
 * Class Xml
 *
 * DTO. XML message fragment.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class Xml implements Fragment
{
    /** @var string XML string */
    public string $xml;

    /**
     * @inheritDoc
     *
     * @return array Message
     */
    public function toMessage(): array
    {
        return [
            "type" => "Json",
            "xml" => $this->xml
        ];
    }
}
