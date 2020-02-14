<?php
namespace DiceRobot\Action\Message\RobotCommand;

use DiceRobot\Base\API;
use DiceRobot\Base\Customization;
use DiceRobot\Base\RobotCommandAction;

/**
 * Class About
 *
 * Action class of order ".robot about". Send about information.
 */
final class About extends RobotCommandAction
{
    public function __invoke(): void
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $template = join("\n", Customization::getCustomFile(DICEROBOT_ABOUT_TEMPLATE_PATH));
        $loginInfo = API::getLoginInfo();

        $this->reply = Customization::getCustomString($template, DICEROBOT_VERSION,
            $loginInfo["data"]["nickname"], $loginInfo["data"]["user_id"], $this->getRobotNickname());
    }
}
