<?php

declare(strict_types=1);

namespace DiceRobot\Data;

use DiceRobot\Traits\ArrayReaderTrait;

/**
 * Class MiraiResponse
 *
 * DTO. Response of Mirai API HTTP plugin APIs.
 *
 * @package DiceRobot\Data
 */
class MiraiResponse
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
}
