<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\API;
use DiceRobot\Base\CheckRule;
use DiceRobot\Base\Customization;
use DiceRobot\Base\RobotSettings;

/**
 * Show description of current rule, or set default rule of COC check dice.
 */
final class SetCOC extends AbstractAction
{
    /** @noinspection PhpUnhandledExceptionInspection */
    public function __invoke(): void
    {
        $ruleIndex = preg_replace("/^\.setcoc[\s]*/i", "", $this->message, 1);

        if ($ruleIndex != "" && !is_numeric($ruleIndex))
        {
            $this->reply = Customization::getCustomReply("setCOCRuleIndexError");
            return;
        }

        $checkRules = Customization::getCustomFile(COC_CHECK_DICE_RULE_PATH)["rules"];
        $rule = new CheckRule($checkRules, $ruleIndex == "" ?
            (RobotSettings::getSetting("cocCheckRule") ?? 0) : intval($ruleIndex));

        if ($ruleIndex == "")
        {
            $this->reply = Customization::getCustomReply("setCOCCurrentRule", $rule->name,
                $rule->description, $rule->intro);
            return;
        }

        if ($this->chatType == "group")
        {
            $userRole = $this->sender->role ?? API::getGroupMemberInfo($this->chatId, $this->userId)["data"]["role"];

            if ($userRole == "member")
            {
                $this->reply = Customization::getCustomReply("setCOCChangeRuleDenied");
                return;
            }
        }

        RobotSettings::setSetting("cocCheckRule", intval($ruleIndex));
        $this->reply = Customization::getCustomReply("setCOCRuleChanged", $rule->name, $rule->description,
            $rule->intro);
    }
}
