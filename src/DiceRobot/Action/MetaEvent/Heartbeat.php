<?php
namespace DiceRobot\Action\MetaEvent;

use DiceRobot\Action\Action;
use DiceRobot\Service\APIService;

/**
 * Heartbeat report to the APIService server.
 */
final class Heartbeat extends Action
{
    public function __invoke(): void
    {
        APIService::heartbeatReport($this->selfId);
        $this->noResponse();
    }
}
