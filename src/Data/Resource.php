<?php

declare(strict_types=1);

namespace DiceRobot\Data;

use DiceRobot\Traits\ArrayReaderTrait;

/**
 * Class Resource
 *
 * DiceRobot resource container.
 *
 * @package DiceRobot\Data
 */
abstract class Resource
{
    use ArrayReaderTrait;

    /**
     * The constructor.
     *
     * @param array $data Resource data
     */
    public function __construct(array $data = [])
    {
        $this->__constructArrayReader($data);
    }

    /**
     * Return JSON serialized data.
     *
     * @return string JSON serialized data
     */
    public function __toString(): string
    {
        return (string) json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
