<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Exception\InformativeException\CharacterCardException\ItemNotExistException;
use DiceRobot\Exception\InformativeException\CharacterCardException\NotBoundException;
use DiceRobot\Exception\InformativeException\CheckRuleException\DangerousException;
use DiceRobot\Exception\InformativeException\CheckRuleException\InvalidException;
use DiceRobot\Exception\InformativeException\CheckRuleException\LostException;
use DiceRobot\Exception\InformativeException\CheckRuleException\MatchFailedException;
use DiceRobot\Exception\InformativeException\DiceException\DiceNumberOverstepException;
use DiceRobot\Exception\InformativeException\DiceException\ExpressionErrorException;
use DiceRobot\Exception\InformativeException\DiceException\SurfaceNumberOverstepException;
use DiceRobot\Exception\InformativeException\IOException\FileDecodeException;
use DiceRobot\Exception\InformativeException\IOException\FileLostException;
use DiceRobot\Exception\InformativeException\OrderErrorException;
use DiceRobot\Exception\InformativeException\ReferenceUndefinedException;
use DiceRobot\Exception\InformativeException\RepeatTimeOverstepException;
use DiceRobot\Service\Container\CharacterCard;
use DiceRobot\Service\Container\CheckRule;
use DiceRobot\Service\Container\Dice\Dice;
use DiceRobot\Service\Customization;

/**
 * Check investigator's attribute or skill.
 */
final class Check extends Action
{
    private bool $private;
    private CheckRule $checkRule;

    /**
     * The constructor.
     *
     * @param object $eventData The event data
     *
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws LostException
     * @throws ReferenceUndefinedException
     */
    public function __construct(object $eventData)
    {
        parent::__construct($eventData);

        $ruleIndex = $this->chatSettings->get("cocCheckRule") ?? 0;
        $this->checkRule = new CheckRule($ruleIndex);
    }

    /**
     * @throws DangerousException
     * @throws DiceNumberOverstepException
     * @throws ExpressionErrorException
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws InternalErrorException
     * @throws InvalidException
     * @throws ItemNotExistException
     * @throws MatchFailedException
     * @throws NetworkErrorException
     * @throws NotBoundException
     * @throws OrderErrorException
     * @throws RepeatTimeOverstepException
     * @throws SurfaceNumberOverstepException
     */
    public function __invoke(): void
    {
        $order = preg_replace("/^\.ra[\s]*/i", "", $this->message, 1);
        $this->checkOrder($order);

        preg_match("/^(h[\s]*)?([bp]([\s]*[1-9][0-9]*)?[\s]+)?/", $order, $optionalOrder);
        $optionalOrder = $optionalOrder[0];
        $order = preg_replace("/^(h[\s]*)?([bp]([\s]*[1-9][0-9]*)?[\s]+)?/",
            "", $order, 1);
        preg_match("/^([\x{4e00}-\x{9fa5}a-z]+|[1-9][0-9]*)/ui", $order, $checkItem);
        $checkItem = $checkItem[0];
        $order = preg_replace("/^([\x{4e00}-\x{9fa5}a-z]+|[1-9][0-9]*)[\s]*/ui",
            "", $order, 1);
        preg_match("/^([+-][1-9][0-9]*)*/", $order, $additional);
        $additional = $additional[0];
        $order = preg_replace("/^([+-][1-9][0-9]*)*[\s]*/",
            "", $order, 1);
        preg_match("/[1-9][0-9]*$/", $order, $repeat);
        $repeat = isset($repeat[0]) ? (int) $repeat[0] : 1;

        $checkValue = $this->getCheckValue($checkItem, $repeat);
        $this->reply .= $repeat > 1 ? "\n" : "";

        if (!$this->checkRange($checkValue, $repeat))
            return;

        $this->check($optionalOrder, $additional, $checkValue, $repeat);

        if ($this->private && $this->chatType == "private")
            $this->reply = Customization::getReply("checkPrivatelyInPrivate");
        elseif ($this->private)
        {
            $this->sendPrivateMessage();
            $this->reply = Customization::getReply("checkPrivately", $this->userNickname, $repeat);
        }
    }

    /**
     * Check the validity of the order.
     *
     * @param string $order The order
     *
     * @throws OrderErrorException
     */
    private function checkOrder(string $order): void
    {
        if (!preg_match("/^(h[\s]*)?([bp]([\s]*[1-9][0-9]*)?[\s]+)?([\x{4e00}-\x{9fa5}a-z]+|[1-9][0-9]*)([\s]*[+-][1-9][0-9]*)*([\s]*#([1-9][0-9]*)?)?$/ui", $order))
            throw new OrderErrorException;
    }

    /**
     * Get the value to be checked.
     *
     * @param string $item The item
     * @param int $repeat Repeat time
     *
     * @return int The value to be checked
     *
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws ItemNotExistException
     * @throws NotBoundException
     */
    private function getCheckValue(string $item, int $repeat): int
    {
        if (preg_match("/^[1-9][0-9]*/", $item, $checkValue))
        {
            $checkValue = (int) $checkValue[0];
            $this->reply = Customization::getReply("checkResultHeading",
                $this->userNickname, $repeat, "");
        }
        elseif (preg_match("/^[\x{4e00}-\x{9fa5}a-z]+/ui", $item, $itemName))
        {
            $itemName = strtoupper($itemName[0]);
            $card = new CharacterCard($this->chatSettings->getCharacterCardId($this->userId));
            $checkValue = $card->get($itemName);
            $this->reply = Customization::getReply("checkResultHeadingWithAttributes",
                $this->userNickname,
                $card->get("HP"), intval(($card->get("SIZ") + $card->get("CON")) / 10),
                $card->get("MP"), intval($card->get("POW") / 5),
                $card->get("SAN"), 99 - $card->get("克苏鲁神话"), $repeat, $itemName);
        }

        return $checkValue;
    }

    /**
     * Check range of the value and repeat time.
     *
     * @param int $value The value
     * @param int $repeat Repeat time
     *
     * @return bool Validity
     *
     * @throws RepeatTimeOverstepException
     */
    private function checkRange(int $value, int $repeat): bool
    {
        if ($value < 1)
        {
            $this->reply = Customization::getReply("checkValueInvalid");
            return false;
        }
        elseif ($value > Customization::getSetting("maxAttribute"))
        {
            $this->reply = Customization::getReply("checkValueTooLarge");
            return false;
        }
        elseif ($repeat < 1 || $repeat > Customization::getSetting("maxRepeatTimes"))
            throw new RepeatTimeOverstepException();

        return true;
    }

    /**
     * Check.
     *
     * @param string $optionalOrder Optional order
     * @param string $additional Additional operations
     * @param int $checkValue The check value
     * @param int $repeat Repeat time
     *
     * @throws ExpressionErrorException
     * @throws DangerousException
     * @throws DiceNumberOverstepException
     * @throws InvalidException
     * @throws MatchFailedException
     * @throws SurfaceNumberOverstepException
     */
    private function check(string $optionalOrder, string $additional, int $checkValue, int $repeat): void
    {
        do
        {
            $dice = new Dice(trim($optionalOrder . " D100"));

            $evalString = "return " . $dice->rollResult . $additional . ";";
            $checkResult = eval($evalString);
            $checkResult = $checkResult < 1 ? 1 : $checkResult;
            $checkResult = $checkResult > 100 ? 100 : $checkResult;
            $rollingResultString = $dice->getCompleteExpression() . $additional .
                ($additional == "" ? "" : "=" . $checkResult);
            $this->reply .= Customization::getReply("checkResult", $rollingResultString, $checkValue,
                    $this->getCheckLevel($checkResult, $checkValue)) . "\n";
        } while (--$repeat);

        $this->reply = trim($this->reply);
        $this->private = $dice->vType == "H";
    }

    /**
     * Get check level according to the check value and the check result.
     *
     * @param int $result The check result
     * @param int $value The check value
     *
     * @return string Check level
     *
     * @throws DangerousException
     * @throws InvalidException
     * @throws MatchFailedException
     */
    private function getCheckLevel(int $result, int $value): string
    {
        $checkLevel = $this->checkRule->getCheckLevel($result, $value);
        return Customization::getWording("checkLevel", $checkLevel);
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
            $privateReply = Customization::getReply("checkPrivatelyInGroup",
                $this->coolq->getGroupInfo($this->chatId)["group_name"], $this->chatId);
        else
            $privateReply = Customization::getReply("checkPrivatelyInDiscuss",
                $this->chatId);

        $privateReply .= $this->reply;
        $this->coolq->sendPrivateMessageAsync($this->userId, $privateReply);
    }
}
