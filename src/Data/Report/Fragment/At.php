<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

use DiceRobot\Interfaces\Fragment\ParsableFragment;

/**
 * Class At
 *
 * DTO. At message fragment.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class At implements ParsableFragment
{
    /** @var int At target ID. */
    public int $target;

    /** @var string|null Displayed text. */
    public string $display;

    /**
     * @inheritDoc
     *
     * @param string $code Mirai code.
     *
     * @return bool Success.
     */
    public function fromMiraiCode(string $code): bool
    {
        if (!preg_match("/^\[mirai:at:([1-9]\d*)(?:,(.*?))?]$/i", $code, $matches)) {
            return false;
        }

        $this->target = (int) $matches[1];
        $this->display = (string) ($matches[2] ?? "");

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
            "type" => "At",
            "target" => $this->target,
            "display" => $this->display
        ];
    }

    /**
     * @inheritDoc
     *
     * @return string Mirai code.
     */
    public function toMiraiCode(): string
    {
        return "[mirai:at:{$this->target},{$this->display}]";
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
