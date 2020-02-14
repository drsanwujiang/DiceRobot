<?php
namespace DiceRobot\Action\Request;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\API;

/**
 * Class GroupInvite
 *
 * Action class of request "group". Approve the request when added to a group, or reject the request when invited to a
 * delinquent group.
 */
final class GroupInvite extends AbstractAction
{
    public function __invoke(): void
    {
        $result = API::queryDelinquentGroup($this->groupId)["data"]["query_result"];  // True, banned

        API::setGroupAddRequestAsync($this->flag, $this->subType, !$result);
        $this->noResponse();
    }
}
