<?php
namespace DiceRobot\Service\API\Response;

use DiceRobot\Service\API\Response;

/**
 * Response of querying group.
 */
class QueryGroupResponse extends Response
{
    public bool $state;

    protected function parse(): void
    {
        $this->state = $this->data["state"];
    }
}
