<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

use DiceRobot\Interfaces\Fragment\ParsableFragment;

/**
 * Class Face
 *
 * DTO. Face message fragment.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class Face implements ParsableFragment
{
    /** @var int QQ face ID. */
    public int $faceId;

    /** @var string QQ face name. */
    public string $name;

    /**
     * @inheritDoc
     *
     * @param string $code Mirai code.
     *
     * @return bool Success.
     */
    public function fromMiraiCode(string $code): bool
    {
        if (!preg_match("/^\[mirai:face:([1-9][0-9]*)(?:,(.+?))?]$/i", $code, $matches)) {
            return false;
        }

        $this->faceId = (int) $matches[1];
        $this->name = empty($matches[2]) ? "" : $matches[2];

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
            "type" => "Face",
            "faceId" => $this->faceId,
            "name" => $this->name
        ];
    }

    /**
     * @inheritDoc
     *
     * @return string Mirai code.
     */
    public function toMiraiCode(): string
    {
        return "[mirai:face:{$this->faceId}]";
    }

    /**
     * @inheritDoc
     *
     * @return string Mirai code.
     */
    public function __toString(): string
    {
        return $this->toMiraiCode();
    }
}
