<?php
namespace DiceRobot\Action\Request;

use DiceRobot\Action\Action;
use DiceRobot\Service\APIService;

/**
 * Approve the request when added to a group, or reject the request when invited to a delinquent group.
 */
final class GroupInvite extends Action
{
    public function __invoke(): void
    {
        // If this group is delinquent, reject the request
        APIService::setGroupAddRequestAsync($this->flag, $this->subType, !$this->queryGroupInfo());
        $this->noResponse();
    }

    /**
     * Query if this group is delinquent.
     *
     * @return bool Delinquent flag
     */
    private function queryGroupInfo(): bool
    {
        return APIService::queryDelinquentGroup($this->groupId)["data"]["query_result"];
    }
}
