<?php
namespace DiceRobot\Action\Message\RobotCommand;

use DiceRobot\Action\RobotCommandAction;
use DiceRobot\Exception\InformativeException\FileUnwritableException;
use DiceRobot\Service\Customization;

/**
 * Set robot activation state.
 */
final class Start extends RobotCommandAction
{
    /**
     * @throws FileUnwritableException
     */
    public function __invoke(): void
    {
        if ($this->commandValue == "" ||
            (
                is_numeric($this->commandValue) &&
                (int) $this->commandValue == $this->selfId ||
                $this->commandValue == substr($this->selfId, -4)
            )
        ) {
            $this->chatSettings->set("active", true);
            $this->reply = Customization::getReply("robotCommandStart", $this->getRobotNickname());
        }
        else
            $this->noResponse();
    }
}
