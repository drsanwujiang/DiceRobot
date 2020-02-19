<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\API;
use DiceRobot\Base\Customization;
use DiceRobot\Base\DiceOperation;

/**
 * Class Dice
 *
 * Action class of order ".r". Roll a dice determined by the rolling expression.
 */
final class Dice extends AbstractAction
{
    private DiceOperation $diceOperation;

    public function __invoke(): void
    {
        $order = preg_replace("/^\.r[\s]*/i", "", $this->message, 1);
        preg_match("/#([1-9][0-9]*)?$/", $order, $repeat);
        $order = preg_replace("/[\s]*#([1-9][0-9]*)?$/", "", $order, 1);
        $repeat = intval(preg_replace("/^#/", "", $repeat[0] ?? "#", 1));
        $repeat = $repeat == 0 ? 1 : $repeat;

        $this->diceOperation = new DiceOperation($order);

        if ($this->diceOperation->success != 0)
        {
            $this->unableToResolve();
            return;
        }

        $replyReasonHeading = ($this->diceOperation->reason == "" ? "" : Customization::getCustomReply(
            "diceRollBecauseOf", $this->diceOperation->reason));
        $replyResultHeading = Customization::getCustomReply("diceRollResult", $this->userNickname);
        $privateInfo = $replyReasonHeading . Customization::getCustomReply("dicePrivateRoll",
                $this->userNickname, $repeat);
        $reply = $replyReasonHeading . $replyResultHeading . ($repeat > 1 ? "\n" : "");

        while ($repeat--)
        {
            $this->diceOperation = new DiceOperation($order);

            if (!$this->diceOperation->bpType && $this->diceOperation->vType == "S")
            {
                $expression = str_replace("*", "×", $this->diceOperation->expression);
                $reply .= $expression . $this->diceOperation->rollResult;
            }
            elseif (!$this->diceOperation->bpType)
            {
                // Normal dice
                $expression = str_replace("*", "×", $this->diceOperation->expression);
                $resultExpression = str_replace("*", "×", $this->diceOperation->toResultExpression());
                $arithmeticExpression = str_replace("*", "×",
                    $this->diceOperation->toArithmeticExpression());
                $reply .= $expression . "=" . $resultExpression;
                $reply .= $resultExpression == $arithmeticExpression ? "" : "=" . $arithmeticExpression;
                $reply .= $this->diceOperation->rollResult == $arithmeticExpression ?
                    "" : "=" . $this->diceOperation->rollResult;
            }
            elseif ($this->diceOperation->vType == "S")
                // B/P dice
                $reply .= $this->diceOperation->bpType . $this->diceOperation->bpDiceNumber . "=" .
                    $this->diceOperation->rollResult;
            else
                // B/P dice
                $reply .= $this->diceOperation->bpType . $this->diceOperation->bpDiceNumber . "=" .
                    $this->diceOperation->toResultExpression() . "[" .
                    Customization::getCustomReply("_BPDiceWording")[$this->diceOperation->bpType] . ":" .
                    join(" ", $this->diceOperation->bpResult) . "]" . "=" . $this->diceOperation->rollResult;

            $reply .= "\n";
        }

        $reply = trim($reply);

        if ($this->diceOperation->vType == "H")
        {
            if ($this->chatType == "private")
            {
                $this->reply = Customization::getCustomReply("dicePrivateRollFromPrivate");
                return;
            }
            elseif ($this->chatType == "group")
                $privateReply = Customization::getCustomReply("dicePrivateRollFromGroup",
                    API::getGroupInfo($this->chatId)["data"]["group_name"], $this->chatId);
            else
                $privateReply = Customization::getCustomReply("dicePrivateRollFromDiscuss", $this->chatId);

            $privateReply .= $reply;
            $this->reply = $privateInfo;
            API::sendPrivateMessageAsync($this->userId, $privateReply);
        }
        else
            $this->reply = $reply;
    }

    protected function unableToResolve(): void
    {
        if ($this->diceOperation->success == -1)
            $this->reply = Customization::getCustomReply("diceWrongNumber");
        elseif ($this->diceOperation->success == -2)
            $this->reply = Customization::getCustomReply("diceWrongExpression");
    }
}
