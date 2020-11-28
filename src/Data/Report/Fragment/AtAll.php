<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

use DiceRobot\Interfaces\Fragment\ParsableFragment;

/**
 * Class AtAll
 *
 * DTO. At all message fragment.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class AtAll implements ParsableFragment
{
    /**
     * @inheritDoc
     *
     * @param string $code Mirai code.
     *
     * @return bool Success.
     */
    public function fromMiraiCode(string $code): bool
    {
        if (!preg_match("/^\[mirai:atall]$/i", $code)) {
            return false;
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
            "type" => "AtAll"
        ];
    }

    /**
     * @inheritDoc
     *
     * @return string Mirai code.
     */
    public function toMiraiCode(): string
    {
        return "[mirai:atall]";
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
