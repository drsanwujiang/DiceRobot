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
    /** @var string|null Image ID */
    public ?string $imageId = null;

    /** @var string|null Image URL */
    public ?string $url = null;

    /** @var string|null Image local path */
    public ?string $path = null;

    /**
     * @inheritDoc
     *
     * @param string $code Mirai code (extended)
     *
     * @return bool Success
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
