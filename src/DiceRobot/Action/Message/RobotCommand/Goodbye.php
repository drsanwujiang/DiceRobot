<?php
namespace DiceRobot\Action\Message\RobotCommand;

use DiceRobot\Action\RobotCommandAction;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Service\Customization;

/**
 * Send information and quit the group.
 */
final class Goodbye extends RobotCommandAction
{
    /**
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    public function __invoke(): void
    {
        if ($this->chatType == "private")
            $this->reply = Customization::getReply("robotCommandGoodbyePrivate");
        elseif ($this->chatType == "discuss")
        {
            if ($this->commandValue == "" ||
                (is_numeric($this->commandValue) &&
                    (
                        (int) $this->commandValue == $this->selfId ||
                        $this->commandValue == substr($this->selfId, -4)
                    )
                )
            ) {
                $this->coolq->sendDiscussMessage($this->chatId, Customization::getReply("robotCommandGoodbye"));
                $this->coolq->setDiscussLeaveAsync($this->chatId);
            }

            $this->noResponse();
        }
        elseif ($this->chatType == "group")
        {
            if ($this->commandValue == "" ||
                (is_numeric($this->commandValue) &&
                    (
                        (int) $this->commandValue == $this->selfId ||
                        $this->commandValue == substr($this->selfId, -4)
                    )
                )
            ) {
                $userRole = $this->sender->role ??
                    $this->coolq->getGroupMemberInfo($this->chatId, $this->userId)["data"]["role"];

                if ($userRole == "owner") {
                    $this->coolq->sendGroupMessage($this->chatId,
                        Customization::getReply("robotCommandGoodbye"));
                    $this->coolq->setGroupLeaveAsync($this->chatId);
                    $this->noResponse();
                }
                else
                    $this->reply = Customization::getReply("robotCommandGoodbyeDenied");
            }
        }
    }
}
