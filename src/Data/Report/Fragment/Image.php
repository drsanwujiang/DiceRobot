<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

use DiceRobot\Interfaces\Fragment\ParsableFragment;

/**
 * Class Image
 *
 * DTO. Image message fragment.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class Image implements ParsableFragment
{
    /** @var string Image ID */
    public string $imageId;

    /** @var string Image URL */
    public string $url;

    /** @var string|null Image local path */
    public ?string $path = NULL;

    /**
     * @inheritDoc
     *
     * @param string $code Mirai code
     *
     * @return bool Success
     */
    public function fromMiraiCode(string $code): bool
    {
        if (!preg_match("/^\[mirai:image:(.*?)]$/i", $code, $matches))
            return false;

        $this->imageId = $matches[1];
        $this->url = "";

        return true;
    }

    /**
     * @inheritDoc
     *
     * @return array Message
     */
    public function toMessage(): array
    {
        return [
            "type" => "Image",
            "imageId" => $this->imageId,
            "url" => $this->url,
            "path" => $this->path
        ];
    }

    /**
     * @inheritDoc
     *
     * @return string Mirai code
     */
    public function toMiraiCode(): string
    {
        return "[mirai:image:{$this->imageId}]";
    }

    /**
     * @inheritDoc
     *
     * @return string Mirai code
     */
    public function __toString(): string
    {
        return $this->toMiraiCode();
    }
}
