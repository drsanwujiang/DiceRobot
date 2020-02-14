<?php
namespace DiceRobot\Action\Notice;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\API;
use DiceRobot\Base\Customization;
use DiceRobot\Base\RobotSettings;

/**
 * Class SelfAdded
 *
 * Action class of notice "group_increase". Send greetings according to hello template when added to a group, or send
 * a message and quit when invited to a delinquent group.
 */
final class SelfAdded extends AbstractAction
{
    public function __invoke(): void
    {
        $result = API::queryDelinquentGroup($this->groupId)["data"]["query_result"];

        if ($result)
        {
            // If group is in black list, quit group
            $message = Customization::getCustomReply("selfAddedBannedGroup");
            API::sendGroupMessage($this->groupId, $message);
            API::setGroupLeaveAsync($this->groupId);
        }
        else
        {
            /** @noinspection PhpUnhandledExceptionInspection */
            $template = join("\n", Customization::getCustomFile(DICEROBOT_HELLO_TEMPLATE_PATH));

            $loginInfo = API::getLoginInfo();
            $message = Customization::getCustomString($template, $loginInfo["data"]["nickname"],
                substr($this->selfId, -4), substr($this->selfId, -4));

            API::sendGroupMessageAsync($this->groupId, $message);
            $this->loadRobotSettings("group", $this->groupId);
            RobotSettings::setSetting("active", true);
        }

        $this->noResponse();
    }
}
