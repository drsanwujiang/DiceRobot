<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action\Action;
use DiceRobot\Exception\InformativeException\CheckRuleException\LostException;
use DiceRobot\Exception\InformativeException\FileLostException;
use DiceRobot\Exception\InformativeException\FileUnwritableException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;
use DiceRobot\Exception\InformativeException\ReferenceUndefinedException;
use DiceRobot\Service\APIService;
use DiceRobot\Service\Container\CheckRule;
use DiceRobot\Service\Customization;

/**
 * Show description of current rule, or set default rule of COC check dice.
 */
final class SetCOC extends Action
{
    /**
     * @throws FileLostException
     * @throws FileUnwritableException
     * @throws JSONDecodeException
     * @throws LostException
     * @throws ReferenceUndefinedException
     */
    public function __invoke(): void
    {
        $ruleIndex = preg_replace("/^\.setcoc[\s]*/i", "", $this->message, 1);

        if ($ruleIndex != "" && !is_numeric($ruleIndex))
        {
            $this->reply = Customization::getReply("setCOCRuleIndexError");
            return;
        }

        $rule = new CheckRule($ruleIndex == "" ?
            ($this->chatSettings->get("cocCheckRule") ?? 0) : (int) $ruleIndex);

        if ($ruleIndex == "")
        {
            $this->reply = Customization::getReply("setCOCCurrentRule", $rule->name,
                $rule->description, $rule->intro);
            return;
        }

        if ($this->chatType == "group")
        {
            $userRole = $this->getUserRole();

            if ($userRole == "member")
            {
                $this->reply = Customization::getReply("setCOCChangeRuleDenied");
                return;
            }
        }

        $this->chatSettings->set("cocCheckRule", (int) $ruleIndex);
        $this->reply = Customization::getReply("setCOCRuleChanged", $rule->name, $rule->description,
            $rule->intro);
    }

    /**
     * Get user's role.
     *
     * @return string User's role
     */
    private function getUserRole(): string
    {
        return $this->sender->role ?? APIService::getGroupMemberInfo($this->chatId, $this->userId)["data"]["role"];
    }
}
