<?php
namespace DiceRobot\Action\MetaEvent;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Exception\InformativeException\APIException\UnexpectedErrorException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;

/**
 * Update robot info when heartbeat event occurs.
 */
final class Heartbeat extends Action
{
    /**
     * @throws InternalErrorException
     * @throws JSONDecodeException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    public function __invoke(): void
    {
        $this->apiService->updateRobot($this->selfId);
        $this->noResponse();
    }
}
