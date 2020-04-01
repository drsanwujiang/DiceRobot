<?php
namespace DiceRobot\Action\Notice;

use DiceRobot\Action\Action;
use DiceRobot\Exception\InformativeException\FileLostException;
use DiceRobot\Exception\InformativeException\FileUnwritableException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;
use DiceRobot\Exception\InformativeException\ReferenceUndefinedException;
use DiceRobot\Service\APIService;
use DiceRobot\Service\Container\Reference;
use DiceRobot\Service\Customization;

/**
 * Send greetings according to the template when added to a group, or send message and quit when invited to a
 * delinquent group.
 */
final class SelfAdded extends Action
{
    /**
     * @throws FileLostException
     * @throws FileUnwritableException
     * @throws JSONDecodeException
     * @throws ReferenceUndefinedException
     */
    public function __invoke(): void
    {
        $delinquent = $this->queryGroupInfo();

        if ($delinquent)
        {
            // Group is in black list, quit
            $message = Customization::getReply("selfAddedBannedGroup");
            APIService::sendGroupMessage($this->groupId, $message);
            APIService::setGroupLeaveAsync($this->groupId);
        }
        else
        {
            $reference = (new Reference("HelloTemplate"))->getString();
            $message = Customization::getString($reference, $this->getLoginInfo()["nickname"],
                substr($this->selfId, -4), substr($this->selfId, -4));
            APIService::sendGroupMessageAsync($this->groupId, $message);
            $this->loadChatSettings("group", $this->groupId);
            $this->chatSettings->set("active", true);
        }

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

    /**
     * Get current robot's info.
     *
     * @return array Info
     */
    private function getLoginInfo(): array
    {
        return APIService::getLoginInfo()["data"];
    }
}
