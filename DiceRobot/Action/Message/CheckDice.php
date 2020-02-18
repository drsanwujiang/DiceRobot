<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\API;
use DiceRobot\Base\CharacterCard;
use DiceRobot\Base\CheckDiceRule;
use DiceRobot\Base\Customization;
use DiceRobot\Base\DiceOperation;
use DiceRobot\Base\RobotSettings;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class CheckDice
 *
 * Action class of order ".ra". Roll a check dice to investigator's attribute or skill.
 */
final class CheckDice extends AbstractAction
{
    /** @noinspection PhpUnhandledExceptionInspection */
    public function __invoke(): void
    {
        $order = preg_replace("/^\.ra[\s]*/i", "", $this->message, 1);

        if (!preg_match("/^(h[\s]*)?([bp]([\s]*[1-9][0-9]*)?[\s]+)?([\x{4e00}-\x{9fa5}]+|[a-z]+|[1-9][0-9]*)([\s]*[+-][1-9][0-9]*)*$/ui",
            $order))
            throw new OrderErrorException;

        preg_match("/^(h[\s]*)?([bp]([\s]*[1-9][0-9]*)?[\s]+)?/", $order, $optionalOrder);
        $optionalOrder = $optionalOrder[0];
        $order = preg_replace("/^(h[\s]*)?([bp]([\s]*[1-9][0-9]*)?[\s]+)?/",
            "", $order, 1);


        if (preg_match("/^[1-9][0-9]*/", $order, $checkValue))
            $checkValue = intval($checkValue[0]);
        elseif (preg_match("/^([\x{4e00}-\x{9fa5}]|[a-z])+/ui", $order, $checkValueName))
        {
            $checkValueName = strtoupper($checkValueName[0]);
            $characterCard = new CharacterCard(RobotSettings::getCharacterCard($this->userId));
            $characterCard->load();
            $checkValue = $characterCard->get($checkValueName);

            if (is_null($checkValue))
            {
                $this->reply = Customization::getCustomReply("checkDiceValueNotFound");
                return;
            }
        }

        $additional = preg_replace("/^([\x{4e00}-\x{9fa5}]+|[a-z]+|[1-9][0-9]*)/ui",
            "", $order, 1);

        if ($checkValue < 1 || $checkValue > Customization::getCustomSetting("maxAttribute"))
        {
            $this->reply = Customization::getCustomReply("checkDiceValueOverRange");
            return;
        }

        $diceOperation = new DiceOperation(trim($optionalOrder . " D100"));

        if ($diceOperation->success != 0)
        {
            $this->reply = Customization::getCustomReply("checkDiceBPNumberOverRange");
            return;
        }

        $evalString = "return " . $diceOperation->rollResult . $additional . ";";
        $checkResult = eval($evalString);
        $checkResult = $checkResult < 1 ? 1 : $checkResult;
        $checkResult = $checkResult > 100 ? 100 : $checkResult;

        if (is_null($diceOperation->bpType))
        {
            // Normal dice
            $rollingResultString = $diceOperation->expression . $additional . "=" .
                $diceOperation->rollResult . $additional . ($additional == "" ? "" : "=" . $checkResult);
        }
        else
            // B/P dice
            $rollingResultString = $diceOperation->bpType . $diceOperation->bpDiceNumber . $additional . "=" .
                $diceOperation->toResultExpression() . "[" .
                Customization::getCustomReply("_BPDiceWording")[$diceOperation->bpType] . ":" .
                join(" ", $diceOperation->bpResult) . "]" . $additional . "=" .
                $diceOperation->rollResult . $additional . ($additional == "" ? "" : "=" . $checkResult);

        $this->reply = Customization::getCustomReply("checkDiceResult",
            $this->userNickname, $rollingResultString, $checkValue,
            $this->checkDiceLevel($checkResult, $checkValue));

        if ($diceOperation->vType === "H")
        {
            if ($this->chatType == "private")
            {
                $this->reply = Customization::getCustomReply("checkDicePrivateChatPrivateCheck");
                return;
            }
            elseif ($this->chatType == "group")
                $privateReply = Customization::getCustomReply("checkDicePrivateCheckFromGroup",
                    API::getGroupInfo($this->chatId)["data"]["group_name"], $this->chatId);
            else
                $privateReply = Customization::getCustomReply("checkDicePrivateCheckFromDiscuss",
                    $this->chatId);

            $privateReply .= $this->reply;
            API::sendPrivateMessageAsync($this->userId, $privateReply);

            $this->reply = Customization::getCustomReply("checkDicePrivateCheck", $this->userNickname);
        }
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    private function checkDiceLevel(int $result, int $value)
    {
        $checkRules = Customization::getCustomFile(COC_CHECK_DICE_RULE_PATH)["rules"];
        $ruleIndex = RobotSettings::getSetting("cocCheckRule") ?? 0;
        $checkLevel = (new CheckDiceRule($checkRules, $ruleIndex))->getCheckLevel($result, $value);

        return Customization::getCustomReply("_checkLevel")[$checkLevel];
    }
}
