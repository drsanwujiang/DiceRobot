<?php
namespace DiceRobot\Action\MetaEvent;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\API;

/**
 * Heartbeat report to the API server.
 */
class Heartbeat extends AbstractAction
{
    public function __invoke(): void
    {
        API::heartbeatReport($this->selfId);
        $this->noResponse();
    }
}
