<?php

declare(strict_types=1);

namespace DiceRobot\Data\Response;

use DiceRobot\Data\Response;

/**
 * Class KowtowResponse
 *
 * DTO. Response of kowtow.
 *
 * @package DiceRobot\Data\Api\Response
 */
final class KowtowResponse extends Response
{
    /** @var int The piety */
    public int $piety;

    /**
     * @inheritDoc
     */
    protected function parse(): void
    {
        $this->piety = (int) $this->data["piety"];
    }
}
