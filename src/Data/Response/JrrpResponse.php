<?php

declare(strict_types=1);

namespace DiceRobot\Data\Response;

use DiceRobot\Data\Response;

/**
 * Class JrrpResponse
 *
 * DTO. Response of Jrrp.
 *
 * @package DiceRobot\Data\Api\Response
 */
final class JrrpResponse extends Response
{
    /** @var int Jrrp, aka luck of today */
    public int $jrrp;

    /**
     * @inheritDoc
     */
    protected function parse(): void
    {
        $this->jrrp = (int) $this->data["jrrp"];
    }
}
