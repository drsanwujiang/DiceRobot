<?php
namespace DiceRobot\Action\Notice;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Exception\InformativeException\APIException\UnexpectedErrorException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;

/**
 * Submit group ID when kicked out of a group.
 */
final class SelfKicked extends Action
{
    /**
     * @throws InternalErrorException
     * @throws JSONDecodeException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    public function __invoke(): void
    {
        $this->submitGroup();  // Submit this group to public database
        $this->noResponse();
    }

    /**
     * Submit the group.
     *
     * @throws InternalErrorException
     * @throws JSONDecodeException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    private function submitGroup(): void
    {
        $this->apiService->auth($this->selfId);
        $this->apiService->submitGroup($this->groupId);
    }
}
