<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

use DiceRobot\Interfaces\Fragment\ParsableFragment;

/**
 * Class Image
 *
 * DTO. Image message fragment.
 *
 * Specially, we extend Mirai code of image, for it can be parsed from path.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class Image implements ParsableFragment
{
    /** @var string|null Image ID. */
    public ?string $imageId = null;

    /** @var string|null Image URL. */
    public ?string $url = null;

    /** @var string|null Image local path. */
    public ?string $path = null;

    /** @var string|null Image base64 encoding. */
    public ?string $base64 = null;

    /**
     * @inheritDoc
     *
     * @param string $code Mirai code (extended).
     *
     * @return bool Success.
     */
    public function fromMiraiCode(string $code): bool
    {
        if (!preg_match("/^\[mirai:image:(.+?)]$/i", $code, $matches)) {
            return false;
        } elseif (preg_match("/^file=(.+)$/i", $content = (string) $matches[1], $matches)) {
            $this->path = (string) $matches[1];
        } else {
            $this->imageId = $content;
        }

        return true;
    }

    /**
     * @inheritDoc
     *
     * @return array Message.
     */
    public function toMessage(): array
    {
        return [
            "type" => "Image",
            "imageId" => $this->imageId,
            "url" => $this->url,
            "path" => $this->path,
            "base64" => $this->base64
        ];
    }

    /**
     * @inheritDoc
     *
     * @return string Mirai code (extended).
     */
    public function toMiraiCode(): string
    {
        if ($this->imageId) {
            return "[mirai:image:{$this->imageId}]";
        } elseif ($this->path) {
            return "[mirai:image:file={$this->path}]";
        } else {
            return "";
        }
    }

    /**
     * @inheritDoc
     *
     * @return string Mirai code (extended).
     */
    public function __toString(): string
    {
        return $this->toMiraiCode();
    }
}
