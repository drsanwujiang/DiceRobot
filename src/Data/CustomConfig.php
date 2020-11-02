<?php

declare(strict_types=1);

namespace DiceRobot\Data;

use DiceRobot\Traits\ArrayReaderTrait;

/**
 * Class CustomConfig
 *
 * DTO. Custom config.
 *
 * @package DiceRobot\Data
 */
class CustomConfig
{
    use ArrayReaderTrait;

    /**
     * The constructor.
     *
     * @param array $data Config data
     */
    public function __construct(array $data = [])
    {
        $this->__constructArrayReader($data);
    }
}
