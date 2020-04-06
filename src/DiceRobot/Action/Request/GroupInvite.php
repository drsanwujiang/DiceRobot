<?php
namespace DiceRobot\Action\Request;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Exception\InformativeException\APIException\UnexpectedErrorException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;

/**
 * Approve the request when added to a group, or reject the request when invited to a delinquent group.
 */
final class GroupInvite extends Action
{
    /**
     * @throws InternalErrorException
     * @throws JSONDecodeException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    public function __invoke(): void
    {
        // If this group is delinquent, reject the request
        $this->coolq->setGroupAddRequestAsync($this->flag, $this->subType, !$this->queryGroup());
        $this->noResponse();
    }

    /**
     * Query if this group is delinquent.
     *
     * @return bool Delinquent
     *
     * @throws InternalErrorException
     * @throws JSONDecodeException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    private function queryGroup(): bool
    {
        return $this->apiService->queryGroup($this->groupId)->state;
    }
}
