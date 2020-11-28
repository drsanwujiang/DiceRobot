<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

use DiceRobot\Interfaces\Fragment;

/**
 * Class Voice
 *
 * DTO. Voice message fragment.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class Voice implements Fragment
{
    /** @var string Voice ID. */
    public string $voiceId;

    /** @var string Voice URL. */
    public string $url;

    /** @var string|null Voice local path. */
    public ?string $path = null;

    /**
     * @inheritDoc
     *
     * @return array Message.
     */
    public function toMessage(): array
    {
        return [
            "type" => "Voice",
            "voiceId" => $this->voiceId,
            "url" => $this->url,
            "path" => $this->path
        ];
    }
}
