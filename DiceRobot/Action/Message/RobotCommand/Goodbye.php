<?php
namespace DiceRobot\Action\Message\RobotCommand;

use DiceRobot\Base\API;
use DiceRobot\Base\Customization;
use DiceRobot\Base\RobotCommandAction;

/**
 * Send information and quit the group.
 */
final class Goodbye extends RobotCommandAction
{
    public function __invoke(): void
    {
        if ($this->chatType == "private")
            $this->reply = Customization::getCustomReply("robotCommandGoodbyePrivate");
        elseif ($this->chatType == "discuss")
        {
            if ($this->commandValue == "" || (is_numeric($this->commandValue) &&
                    (intval($this->commandValue) == $this->selfId ||
                        $this->commandValue == substr($this->selfId, -4))))
            {
                API::sendDiscussMessage($this->chatId, Customization::getCustomReply("robotCommandGoodbye"));
                API::setDiscussLeaveAsync($this->chatId);
            }

            $this->noResponse();
        }
        elseif ($this->chatType == "group")
        {
            if ($this->commandValue == "" || (is_numeric($this->commandValue) &&
                    (intval($this->commandValue) == $this->selfId ||
                        $this->commandValue == substr($this->selfId, -4))))
            {
                $userRole = $this->sender->role ??
                    API::getGroupMemberInfo($this->chatId, $this->userId)["data"]["role"];

                if ($userRole == "owner") {
                    API::sendGroupMessage($this->chatId,
                        Customization::getCustomReply("robotCommandGoodbye"));
                    API::setGroupLeaveAsync($this->chatId);
                    $this->noResponse();
                }
                else
                    $this->reply = Customization::getCustomReply("robotCommandGoodbyeDenied");
            }
        }
    }
}
