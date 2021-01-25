<?php

declare(strict_types=1);

namespace DiceRobot\Data\Response;

use DiceRobot\Data\DiceRobotResponse;

/**
 * Class GetPietyResponse
 *
 * DTO. Response of getting piety.
 *
 * @package DiceRobot\Data\Api\Response
 */
final class GetPietyResponse extends DiceRobotResponse
{
    /** @var int Piety. */
    public int $piety;

    /**
     * @inheritDoc
     */
    protected function parse(): void
    {
        $this->piety = (int) $this->data["piety"];
    }
}
