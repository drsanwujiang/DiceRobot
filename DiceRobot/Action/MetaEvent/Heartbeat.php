<?php
namespace DiceRobot\Action\MetaEvent;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\API;

/**
 * Class Heartbeat
 *
 * Action class of meta event "heartbeat". Execute periodic order.
 */
class Heartbeat extends AbstractAction
{
    public function __invoke(): void
    {
        // Report to API server
        API::heartbeatReport($this->selfId);
        $this->noResponse();
    }
}
