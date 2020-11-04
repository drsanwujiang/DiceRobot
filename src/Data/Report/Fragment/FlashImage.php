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
    /** @var string Image ID */
    public string $imageId;

    /** @var string Image URL */
    public string $url;

    /** @var string|null Image local path */
    public ?string $path = null;

    /**
     * @inheritDoc
     *
     * @return array Message
     */
    public function toMessage(): array
    {
        return [
            "type" => "FlashImage",
            "imageId" => $this->imageId,
            "url" => $this->url,
            "path" => $this->path
        ];
    }
}
