<?php

declare(strict_types=1);

namespace DiceRobot\Interfaces\Fragment;

use DiceRobot\Interfaces\Fragment;

/**
 * Interface ParsableFragment
 *
 * Describe a parsable fragment of Mirai message chain, which can be serialized to (or deserialized from) Mirai code.
 *
 * @package DiceRobot\Interfaces\Fragment
 */
interface ParsableFragment extends Fragment
{
    /**
     * Deserialize Mirai code to fragment.
     *
     * @param string $code Mirai code
     *
     * @return bool Success
     */
    public function fromMiraiCode(string $code): bool;

    /**
     * Serialize fragment to Mirai code.
     *
     * @return string Mirai code
     */
    public function toMiraiCode(): string;

    /**
     * Alias of toMiraiCode().
     *
     * @return string Mirai code
     */
    public function __toString(): string;
}
