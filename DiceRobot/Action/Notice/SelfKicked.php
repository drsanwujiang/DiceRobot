<?php
namespace DiceRobot\Action\Notice;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\API;

/**
 * Class SelfKicked
 *
 * Action class of notice "group_decrease". Submit group ID when kicked out of a group.
 */
final class SelfKicked extends AbstractAction
{
    public function __invoke(): void
    {
        // Get credential
        $result = API::getAPICredential($this->selfId);

        if ($result["code"] != 0)
            error_log("DiceRobot submits delinquent group failed: " . $result["message"] . "\n" .
                "Delinquent group ID: " . $this->groupId);
        else
        {
            $credential = $result["data"]["credential"];

            // Submit this group to public database
            API::submitDelinquentGroup($this->groupId, $credential);
        }

        $this->noResponse();
    }
}
