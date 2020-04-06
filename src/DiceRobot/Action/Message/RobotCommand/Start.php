<?php
namespace DiceRobot\Action\Message\RobotCommand;

use DiceRobot\Action\RobotCommandAction;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Exception\InformativeException\IOException\FileUnwritableException;
use DiceRobot\Service\Customization;

/**
 * Set robot activation state.
 */
final class Start extends RobotCommandAction
{
    /**
     * @throws FileUnwritableException
     * @throws InternalErrorException
     * @throws NetworkErrorException
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
