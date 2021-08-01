<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

use DiceRobot\Interfaces\Fragment;

/**
 * Class FlashImage
 *
 * DTO. Flash image message fragment.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class FlashImage implements Fragment
{
    /** @var string|null Flash image ID. */
    public ?string $imageId = null;

    /** @var string|null Flash image URL. */
    public ?string $url = null;

    /** @var string|null Flash image local path. */
    public ?string $path = null;

    /** @var string|null Flash image base64 encoding. */
    public ?string $base64 = null;

    /**
     * @inheritDoc
     *
     * @return array Message.
     */
    public function toMessage(): array
    {
        return [
            "type" => "FlashImage",
            "imageId" => $this->imageId,
            "url" => $this->url,
            "path" => $this->path,
            "base64" => $this->base64
        ];
    }
}
