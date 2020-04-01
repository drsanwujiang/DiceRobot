<?php
namespace DiceRobot\Action\Message\RobotCommand;

use DiceRobot\Action\RobotCommandAction;
use DiceRobot\Service\APIService;
use DiceRobot\Service\Customization;

/**
 * Send information and quit the group.
 */
final class Goodbye extends RobotCommandAction
{
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
                APIService::sendDiscussMessage($this->chatId, Customization::getReply("robotCommandGoodbye"));
                APIService::setDiscussLeaveAsync($this->chatId);
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
                    APIService::getGroupMemberInfo($this->chatId, $this->userId)["data"]["role"];

                if ($userRole == "owner") {
                    APIService::sendGroupMessage($this->chatId,
                        Customization::getReply("robotCommandGoodbye"));
                    APIService::setGroupLeaveAsync($this->chatId);
                    $this->noResponse();
                }
                else
                    $this->reply = Customization::getReply("robotCommandGoodbyeDenied");
            }
        }
    }
}
