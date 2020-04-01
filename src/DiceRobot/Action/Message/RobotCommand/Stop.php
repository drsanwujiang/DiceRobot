<?php
namespace DiceRobot\Action\Message\RobotCommand;

use DiceRobot\Action\RobotCommandAction;
use DiceRobot\Exception\InformativeException\FileUnwritableException;
use DiceRobot\Service\Customization;

/**
 * Set robot inactivation state.
 */
final class Stop extends RobotCommandAction
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
            $this->chatSettings->set("active", false);
            $this->reply = Customization::getReply("robotCommandStop");
        }
        else
            $this->noResponse();
    }
}
