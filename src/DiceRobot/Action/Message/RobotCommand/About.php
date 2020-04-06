<?php
namespace DiceRobot\Action\Message\RobotCommand;

use DiceRobot\Action\RobotCommandAction;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Exception\InformativeException\IOException\FileDecodeException;
use DiceRobot\Exception\InformativeException\IOException\FileLostException;
use DiceRobot\Exception\InformativeException\ReferenceUndefinedException;
use DiceRobot\Service\Container\Reference;
use DiceRobot\Service\Customization;

/**
 * Send about information.
 */
final class About extends RobotCommandAction
{
    /**
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws InternalErrorException
     * @throws NetworkErrorException
     * @throws ReferenceUndefinedException
     */
    public function __invoke(): void
    {
        $template = (new Reference("AboutTemplate"))->getString();
        $loginInfo = $this->coolq->getLoginInfo();
        $this->reply = Customization::getString($template, DICEROBOT_VERSION,
            $loginInfo["nickname"], $loginInfo["user_id"], $this->getRobotNickname());
    }
}
