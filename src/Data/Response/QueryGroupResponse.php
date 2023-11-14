<?php

declare(strict_types=1);

namespace DiceRobot\Data\Response;

use DiceRobot\Data\DiceRobotResponse;

/**
 * Class QueryGroupResponse
 *
 * DTO. Response of querying group.
 *
 * @package DiceRobot\Data\Response
 */
final class QueryGroupResponse extends DiceRobotResponse
{
    /** @var bool Group state, TRUE for normal and FALSE for delinquent. */
    public bool $state;

    /**
     * @inheritDoc
     */
    protected function parse(): void
    {
        $this->state = (bool) $this->data["state"];
    }
}
