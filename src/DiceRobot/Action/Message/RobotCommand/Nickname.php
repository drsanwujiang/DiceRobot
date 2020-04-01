<?php
namespace DiceRobot\Action\Message\RobotCommand;

use DiceRobot\Action\RobotCommandAction;
use DiceRobot\Exception\InformativeException\FileUnwritableException;
use DiceRobot\Service\APIService;
use DiceRobot\Service\Customization;

/**
 * Set nickname of robot.
 */
final class Nickname extends RobotCommandAction
{
    /**
     * @throws FileUnwritableException
     */
    public function __invoke(): void
    {
        $robotNickname = $this->commandValue;

        if ($this->chatType != "private") APIService::setGroupCardAsync($this->chatId, $this->selfId, $robotNickname);

        if ($robotNickname == "")
        {
            $this->chatSettings->set("robotNickname", NULL);
            $this->reply = Customization::getReply("robotCommandNicknameUnset");
        }
        else
        {
            $this->chatSettings->set("robotNickname", $robotNickname);
            $this->reply = Customization::getReply("robotCommandNicknameChanged", $robotNickname);
        }
    }
}
