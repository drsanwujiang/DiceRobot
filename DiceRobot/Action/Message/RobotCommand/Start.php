<?php
namespace DiceRobot\Action\Message\RobotCommand;

use DiceRobot\Base\Customization;
use DiceRobot\Base\RobotCommandAction;
use DiceRobot\Base\RobotSettings;

/**
 * Set robot activation state.
 */
final class Start extends RobotCommandAction
{
    public function __invoke(): void
    {
        if ($this->commandValue == "" || (is_numeric($this->commandValue) &&
                (intval($this->commandValue) == $this->selfId ||
                    $this->commandValue == substr($this->selfId, -4))))
        {
            RobotSettings::setSetting("active", true);
            $this->reply = Customization::getCustomReply("robotCommandStart", $this->getRobotNickname());
        }
        else
            $this->noResponse();
    }
}
