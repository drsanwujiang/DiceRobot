<?php
namespace DiceRobot\Action\Message\RobotCommand;

use DiceRobot\Action\RobotCommandAction;
use DiceRobot\Service\APIService;
use DiceRobot\Service\Container\Reference;
use DiceRobot\Service\Customization;

/**
 * Send about information.
 */
final class About extends RobotCommandAction
{
    public function __invoke(): void
    {
        $template = (new Reference("AboutTemplate"))->getString();
        $loginInfo = APIService::getLoginInfo();
        $this->reply = Customization::getString($template, DICEROBOT_VERSION,
            $loginInfo["data"]["nickname"], $loginInfo["data"]["user_id"], $this->getRobotNickname());
    }
}
