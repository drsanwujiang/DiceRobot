<?php
namespace DiceRobot\Action\Message\RobotCommand;

use DiceRobot\Base\Customization;
use DiceRobot\Base\RobotCommandAction;
use DiceRobot\Base\RobotSettings;

/**
 * Class Start
 *
 * Action class of order ".robot start". Set robot active.
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
