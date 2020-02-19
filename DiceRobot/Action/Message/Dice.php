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
        $this->diceOperation = new DiceOperation($order);

        if ($this->diceOperation->success != 0)
        {
            $this->unableToResolve();
            return;
        }

        $replyReasonHeading = $this->diceOperation->reason == "" ? "" :
            Customization::getCustomReply("diceRollBecauseOf", $this->diceOperation->reason);
        $replyResultHeading = Customization::getCustomReply("diceRollResult", $this->userNickname);
        $reply = "";

        if (!$this->diceOperation->bpType)
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
        else
            // B/P dice
            $reply .= $this->diceOperation->bpType . $this->diceOperation->bpDiceNumber . "=" .
                $this->diceOperation->toResultExpression() . "[" .
                Customization::getCustomReply("_BPDiceWording")[$this->diceOperation->bpType] . ":" .
                join(" ", $this->diceOperation->bpResult) . "]" . "=" . $this->diceOperation->rollResult;

        if (!$this->diceOperation->vType)
            $this->reply = $replyReasonHeading . $replyResultHeading . $reply;
        elseif ($this->diceOperation->vType === "H")
        {
            if ($this->chatType == "private")
            {
                $this->reply = Customization::getCustomReply("dicePrivateChatPrivateRoll");
                return;
            }
            elseif ($this->chatType == "group")
                $privateReply = Customization::getCustomReply("dicePrivateRollFromGroup",
                    API::getGroupInfo($this->chatId)["data"]["group_name"], $this->chatId);
            else
                $privateReply = Customization::getCustomReply("dicePrivateRollFromDiscuss", $this->chatId);

            $privateReply .= $replyReasonHeading . $replyResultHeading . $reply;
            API::sendPrivateMessageAsync($this->userId, $privateReply);

            $this->reply = $replyReasonHeading .
                Customization::getCustomReply("dicePrivateRoll", $this->userNickname);
        }
        elseif ($this->diceOperation->vType === "S")
        {
            $splitReply = explode("=", $reply);
            $this->reply = $replyReasonHeading . $replyResultHeading . $splitReply[0] . "=" . end($splitReply);
        }
    }

    protected function unableToResolve(): void
    {
        if ($this->diceOperation->success == -1)
            $this->reply = Customization::getCustomReply("diceWrongNumber");
        elseif ($this->diceOperation->success == -2)
            $this->reply = Customization::getCustomReply("diceWrongExpression");
    }
}
