<?php

declare(strict_types=1);

namespace DiceRobot\Data\Response;

use DiceRobot\Data\DiceRobotResponse;

/**
 * Class GetLuckResponse
 *
 * DTO. Response of getting luck.
 *
 * @package DiceRobot\Data\Api\Response
 */
final class GetLuckResponse extends DiceRobotResponse
{
    /** @var int Today's luck. */
    public int $luck;

    /**
     * @inheritDoc
     */
    protected function parse(): void
    {
        $this->luck = (int) $this->data["luck"];
    }
}
