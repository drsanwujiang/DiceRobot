<?php
namespace DiceRobot\Action\Message\RobotCommand;

use DiceRobot\Base\API;
use DiceRobot\Base\Customization;
use DiceRobot\Base\RobotCommandAction;
use DiceRobot\Base\RobotSettings;

/**
 * Set nickname of robot.
 */
final class Nickname extends RobotCommandAction
{
    public function __invoke(): void
    {
        $robotNickname = $this->commandValue;

        if ($this->chatType != "private") API::setGroupCardAsync($this->chatId, $this->selfId, $robotNickname);

        if ($robotNickname == "")
        {
            RobotSettings::setSetting("robotNickname", NULL);
            $this->reply = Customization::getCustomReply("robotCommandNicknameUnset");
        }
        else
        {
            RobotSettings::setSetting("robotNickname", $robotNickname);
            $this->reply = Customization::getCustomReply("robotCommandNicknameChanged", $robotNickname);
        }
    }
}
