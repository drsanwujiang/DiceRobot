<?php
namespace DiceRobot\Action\Notice;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Exception\InformativeException\APIException\UnexpectedErrorException;
use DiceRobot\Exception\InformativeException\IOException\FileDecodeException;
use DiceRobot\Exception\InformativeException\IOException\FileLostException;
use DiceRobot\Exception\InformativeException\IOException\FileUnwritableException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;
use DiceRobot\Exception\InformativeException\ReferenceUndefinedException;
use DiceRobot\Service\Container\Reference;
use DiceRobot\Service\Customization;

/**
 * Send greetings according to the template when added to a group, or send message and quit when invited to a
 * delinquent group.
 */
final class SelfAdded extends Action
{
    /**
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws FileUnwritableException
     * @throws InternalErrorException
     * @throws JSONDecodeException
     * @throws NetworkErrorException
     * @throws ReferenceUndefinedException
     * @throws UnexpectedErrorException
     */
    public function __invoke(): void
    {
        $state = $this->queryGroup();

        if ($state)
        {
            // Group is in black list, quit
            $message = Customization::getReply("selfAddedBannedGroup");
            $this->coolq->sendGroupMessage($this->groupId, $message);
            $this->coolq->setGroupLeaveAsync($this->groupId);
        }
        else
        {
            $reference = (new Reference("HelloTemplate"))->getString();
            $message = Customization::getString($reference, $this->coolq->getLoginInfo()["nickname"],
                substr($this->selfId, -4), substr($this->selfId, -4));
            $this->coolq->sendGroupMessage($this->groupId, $message);
            $this->loadChatSettings("group", $this->groupId);
            $this->chatSettings->set("active", true);
        }

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
        $this->apiService->auth($this->selfId);
        return $this->apiService->queryGroup($this->groupId)->state;
    }
}
