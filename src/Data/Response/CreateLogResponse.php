<?php

declare(strict_types=1);

namespace DiceRobot\Data\Response;

use DiceRobot\Data\DiceRobotResponse;

/**
 * Class CreateLogResponse
 *
 * DTO. Response of creating log.
 *
 * @package DiceRobot\Data\Response
 */
final class CreateLogResponse extends DiceRobotResponse
{
    /** @var string Log UUID. */
    public string $uuid;

    /**
     * @inheritDoc
     */
    protected function parse(): void
    {
        $this->uuid = (string) $this->data["uuid"];
    }
}
