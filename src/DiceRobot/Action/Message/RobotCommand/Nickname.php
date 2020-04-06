<?php
namespace DiceRobot\Action\Message\RobotCommand;

use DiceRobot\Action\RobotCommandAction;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Exception\InformativeException\IOException\FileUnwritableException;
use DiceRobot\Service\Customization;

/**
 * Set nickname of robot.
 */
final class Nickname extends RobotCommandAction
{
    /**
     * @throws FileUnwritableException
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    public function __invoke(): void
    {
        $robotNickname = $this->commandValue;

        if ($this->chatType != "private")
            $this->coolq->setGroupCardAsync($this->chatId, $this->selfId, $robotNickname);

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
