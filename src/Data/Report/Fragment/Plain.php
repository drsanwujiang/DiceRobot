<?php

declare(strict_types=1);

namespace DiceRobot\Data\Report\Fragment;

use DiceRobot\Interfaces\Fragment\ParsableFragment;

/**
 * Class Plain
 *
 * DTO. Plain text message fragment.
 *
 * @package DiceRobot\Data\Report\Fragment
 */
final class Plain implements ParsableFragment
{
    /** @var string Plain text */
    public string $text;

    /**
     * @inheritDoc
     *
     * @param string $code Mirai code
     *
     * @return bool Success
     */
    public function fromMiraiCode(string $code): bool
    {
        $this->text = $code;

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
            "type" => "Plain",
            "text" => $this->text
        ];
    }

    /**
     * @inheritDoc
     *
     * @return string Mirai code
     */
    public function toMiraiCode(): string
    {
        return $this->text;
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
