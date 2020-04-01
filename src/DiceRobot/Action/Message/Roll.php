<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action\Action;
use DiceRobot\Exception\ArithmeticExpressionErrorException;
use DiceRobot\Exception\InformativeException\DiceException\DiceNumberOverstepException;
use DiceRobot\Exception\InformativeException\DiceException\SurfaceNumberOverstepException;
use DiceRobot\Exception\InformativeException\RepeatTimeOverstepException;
use DiceRobot\Service\APIService;
use DiceRobot\Service\Container\Dice\Dice;
use DiceRobot\Service\Customization;

/**
 * Roll a dice determined by the rolling expression.
 */
final class Roll extends Action
{
    /**
     * @throws ArithmeticExpressionErrorException
     * @throws DiceNumberOverstepException
     * @throws RepeatTimeOverstepException
     * @throws SurfaceNumberOverstepException
     */
    public function __invoke(): void
    {
        $order = preg_replace("/^\.r[\s]*/i", "", $this->message, 1);

        preg_match("/#([1-9][0-9]*)?$/", $order, $repeat);
        $order = preg_replace("/[\s]*#([1-9][0-9]*)?$/", "", $order, 1);
        $repeat = (int) preg_replace("/^#/", "", $repeat[0] ?? "#", 1);
        $repeat = $repeat == 0 ? 1 : $repeat;

        if (!$this->checkRange($repeat))
            return;

        $dice = new Dice($order);
        $replyReasonHeading = ($dice->reason == "" ? "" : Customization::getReply(
            "rollBecauseOf", $dice->reason));
        $replyResultHeading = Customization::getReply("rollResult", $this->userNickname);
        $privateInfo = $replyReasonHeading . Customization::getReply("rollPrivately",
                $this->userNickname, $repeat);
        $this->reply = $replyReasonHeading . $replyResultHeading . ($repeat > 1 ? "\n" : "");

        while ($repeat--)
        {
            $dice = new Dice($order);
            $this->reply .= $dice->getCompleteExpression() . "\n";
        }

        $this->reply = trim($this->reply);
        $private = $dice->vType == "H";

        if ($private && $this->chatType == "private")
            $this->reply = Customization::getReply("rollPrivatelyInPrivate");
        elseif ($private)
        {
            $this->sendPrivateMessage();
            $this->reply = $privateInfo;
        }
    }

    /**
     * Check the repeat time range.
     *
     * @param int $repeat Repeat time
     *
     * @return bool Flag of validity
     *
     * @throws RepeatTimeOverstepException
     */
    private function checkRange(int $repeat): bool
    {
        if ($repeat < 1 || $repeat > Customization::getSetting("maxRepeatTimes"))
            throw new RepeatTimeOverstepException();

        return true;
    }

    /**
     * Send private message.
     */
    private function sendPrivateMessage(): void
    {
        if ($this->chatType == "group")
            $privateReply = Customization::getReply("rollPrivatelyInGroup",
                APIService::getGroupInfo($this->chatId)["data"]["group_name"], $this->chatId);
        else
            $privateReply = Customization::getReply("rollPrivatelyInDiscuss", $this->chatId);

        $privateReply .= $this->reply;
        APIService::sendPrivateMessageAsync($this->userId, $privateReply);
    }
}
