<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Exception\InformativeException\DiceException\DiceNumberOverstepException;
use DiceRobot\Exception\InformativeException\DiceException\ExpressionErrorException;
use DiceRobot\Exception\InformativeException\DiceException\SurfaceNumberOverstepException;
use DiceRobot\Exception\InformativeException\RepeatTimeOverstepException;
use DiceRobot\Service\Container\Dice\Dice;
use DiceRobot\Service\Customization;

/**
 * Roll a dice determined by the rolling expression.
 */
final class Roll extends Action
{
    /**
     * @throws DiceNumberOverstepException
     * @throws ExpressionErrorException
     * @throws InternalErrorException
     * @throws NetworkErrorException
     * @throws RepeatTimeOverstepException
     * @throws SurfaceNumberOverstepException
     */
    public function __invoke(): void
    {
        $order = preg_replace("/^\.r[\s]*/i", "", $this->message);

        // Parse the order
        preg_match("/^(.*?)(?:#([1-9][0-9]*))?$/", $order, $matches);
        $diceOrder = $matches[1] ?? "";
        $repeat = empty($matches[2] ?? "") ? 1 : (int) $matches[2];

        if (!$this->checkRange($repeat))
            return;

        $dice = new Dice($diceOrder);
        $replyReasonHeading = ($dice->reason == "" ? "" : Customization::getReply(
            "rollBecauseOf", $dice->reason));
        $replyResultHeading = Customization::getReply("rollResult", $this->userNickname);
        $privateInfo = $replyReasonHeading . Customization::getReply("rollPrivately",
                $this->userNickname, $repeat);
        $this->reply = $replyReasonHeading . $replyResultHeading . ($repeat > 1 ? "\n" : "");

        while ($repeat--)
        {
            $dice = new Dice($diceOrder);
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
     * Check range of repeat time.
     *
     * @param int $repeat Repeat time
     *
     * @return bool Validity
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
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    private function sendPrivateMessage(): void
    {
        if ($this->chatType == "group")
            $privateReply = Customization::getReply("rollPrivatelyInGroup",
                $this->coolq->getGroupInfo($this->chatId)["group_name"], $this->chatId);
        else
            $privateReply = Customization::getReply("rollPrivatelyInDiscuss", $this->chatId);

        $privateReply .= $this->reply;
        $this->coolq->sendPrivateMessageAsync($this->userId, $privateReply);
    }
}
