<?php

declare(strict_types=1);

namespace DiceRobot\Data\Response;

use DiceRobot\Data\DiceRobotResponse;

/**
 * Class FinishLogResponse
 *
 * DTO. Response of finishing log.
 *
 * @package DiceRobot\Data\Response
 */
final class FinishLogResponse extends DiceRobotResponse
{
    /** @var string Log URL. */
    public string $url;

    /**
     * @inheritDoc
     */
    protected function parse(): void
    {
        $this->url = (string) $this->data["url"];
    }
}
