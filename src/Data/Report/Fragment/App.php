<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

use DiceRobot\Interfaces\Fragment;

/**
 * Class App
 *
 * DTO. App message fragment.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class App implements Fragment
{
    /** @var string App content */
    public string $content;

    /**
     * @inheritDoc
     *
     * @return array Message
     */
    public function toMessage(): array
    {
        return [
            "type" => "App",
            "content" => $this->content
        ];
    }
}
