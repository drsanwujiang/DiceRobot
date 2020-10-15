<?php

declare(strict_types=1);

namespace DiceRobot\Data\Response;

use DiceRobot\Data\Response;

/**
 * Class QueryGroupResponse
 *
 * DTO. Response of querying group.
 *
 * @package DiceRobot\Data\Response
 */
final class QueryGroupResponse extends Response
{
    /** @var bool Delinquency state */
    public bool $state;

    /**
     * @inheritDoc
     */
    protected function parse(): void
    {
        $this->state = (bool) $this->data["state"];
    }
}
