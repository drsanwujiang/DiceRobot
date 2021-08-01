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
    /** @var string|null Voice ID. */
    public ?string $voiceId = null;

    /** @var string|null Voice URL. */
    public ?string $url = null;

    /** @var string|null Voice local path. */
    public ?string $path = null;

    /** @var string|null Voice base64 encoding. */
    public ?string $base64 = null;

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
            "path" => $this->path,
            "base64" => $this->base64
        ];
    }
}
