<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\Customization;
use DiceRobot\Base\RobotSettings;

/**
 * Set/Unset nickname of group member.
 */
final class Nickname extends AbstractAction
{
    public function __invoke(): void
    {
        $nickname = preg_replace("/^\.nn[\s]*/i", "", $this->message, 1);

        if ($nickname == "")
        {
            RobotSettings::setNickname($this->userId, NULL);
            $this->reply = Customization::getCustomReply("nicknameUnset", $this->userName);
        }
        else
        {
            RobotSettings::setNickname($this->userId, $nickname);
            $this->reply = Customization::getCustomReply("nicknameChanged", $this->userNickname, $nickname);
        }
    }
}
