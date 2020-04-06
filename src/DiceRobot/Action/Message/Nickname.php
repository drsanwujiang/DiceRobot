<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException\IOException\FileUnwritableException;
use DiceRobot\Service\Customization;

/**
 * Set/Unset nickname of group member.
 */
final class Nickname extends Action
{
    /**
     * @throws FileUnwritableException
     */
    public function __invoke(): void
    {
        $nickname = preg_replace("/^\.nn[\s]*/i", "", $this->message, 1);

        if ($nickname == "")
        {
            $this->chatSettings->setNickname($this->userId, NULL);
            $this->reply = Customization::getReply("nicknameUnset", $this->userName);
        }
        else
        {
            $this->chatSettings->setNickname($this->userId, $nickname);
            $this->reply = Customization::getReply("nicknameChanged", $this->userNickname, $nickname);
        }
    }
}
