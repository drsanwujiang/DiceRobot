<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Exception\InformativeException\CheckRuleException\LostException;
use DiceRobot\Exception\InformativeException\IOException\FileDecodeException;
use DiceRobot\Exception\InformativeException\IOException\FileLostException;
use DiceRobot\Exception\InformativeException\IOException\FileUnwritableException;
use DiceRobot\Exception\InformativeException\ReferenceUndefinedException;
use DiceRobot\Service\Container\CheckRule;
use DiceRobot\Service\Customization;

/**
 * Show description of current rule, or set default rule of COC check dice.
 */
final class SetCOC extends Action
{
    /**
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws FileUnwritableException
     * @throws InformativeException
     * @throws InternalErrorException
     * @throws LostException
     * @throws NetworkErrorException
     * @throws ReferenceUndefinedException
     */
    public function __invoke(): void
    {
        $ruleIndex = preg_replace("/^\.setcoc[\s]*/i", "", $this->message);

        if ($ruleIndex != "" && !is_numeric($ruleIndex))
            throw new InformativeException("setCOCRuleIndexError");

        $rule = new CheckRule(
            $ruleIndex == "" ? ($this->chatSettings->get("cocCheckRule") ?? 0) : (int) $ruleIndex
        );

        // Show rule details
        if ($ruleIndex == "")
        {
            $this->reply = Customization::getReply("setCOCCurrentRule",
                $rule->name, $rule->description, $rule->intro);
            return;
        }

        if ($this->chatType == "group")
        {
            $userRole = $this->getUserRole();

            if ($userRole == "member")
                throw new InformativeException("setCOCChangeRuleDenied");
        }

        $this->chatSettings->set("cocCheckRule", (int) $ruleIndex);
        $this->reply = Customization::getReply("setCOCRuleChanged",
            $rule->name, $rule->description, $rule->intro);
    }

    /**
     * Get user's role.
     *
     * @return string User's role
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    private function getUserRole(): string
    {
        return $this->sender->role ?? $this->coolq->getGroupMemberInfo($this->chatId, $this->userId)["role"];
    }
}
