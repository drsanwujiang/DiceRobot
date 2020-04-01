<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action\Action;
use DiceRobot\Exception\ArithmeticExpressionErrorException;
use DiceRobot\Exception\InformativeException\CharacterCardException\ItemNotExistException;
use DiceRobot\Exception\InformativeException\CharacterCardException\NotBoundException;
use DiceRobot\Exception\InformativeException\CheckRuleException\DangerousException;
use DiceRobot\Exception\InformativeException\CheckRuleException\InvalidException;
use DiceRobot\Exception\InformativeException\CheckRuleException\LostException;
use DiceRobot\Exception\InformativeException\CheckRuleException\MatchFailedException;
use DiceRobot\Exception\InformativeException\DiceException\DiceNumberOverstepException;
use DiceRobot\Exception\InformativeException\DiceException\SurfaceNumberOverstepException;
use DiceRobot\Exception\InformativeException\FileLostException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;
use DiceRobot\Exception\InformativeException\OrderErrorException;
use DiceRobot\Exception\InformativeException\ReferenceUndefinedException;
use DiceRobot\Exception\InformativeException\RepeatTimeOverstepException;
use DiceRobot\Service\APIService;
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
     * Constructor.
     *
     * @param object $eventData Event data
     *
     * @throws FileLostException
     * @throws JSONDecodeException
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
     * @throws ArithmeticExpressionErrorException
     * @throws DangerousException
     * @throws DiceNumberOverstepException
     * @throws FileLostException
     * @throws InvalidException
     * @throws ItemNotExistException
     * @throws JSONDecodeException
     * @throws MatchFailedException
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
     * @param string $order Order
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
     * @param string $item Item
     * @param int $repeat Repeat time
     *
     * @return int The value to be checked
     *
     * @throws FileLostException
     * @throws ItemNotExistException
     * @throws JSONDecodeException
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
            $cardId = $this->chatSettings->getCharacterCardId($this->userId);
            $card = new CharacterCard($cardId);
            $card->load();
            $checkValue = $card->get($itemName);
            $this->reply = Customization::getReply("checkResultHeadingWithAttributes",
                $this->userNickname,
                $card->get("HP"), intval(($card->get("SIZ") + $card->get("CON")) / 10),
                $card->get("MP"), intval($card->get("POW") / 5),
                $card->get("SAN"), 99 - $card->get("克苏鲁神话") ?? 0,
                $repeat, $itemName);
        }

        return $checkValue;
    }

    /**
     * Check the value and the repeat time range.
     *
     * @param int $value Value
     * @param int $repeat Repeat time
     *
     * @return bool Flag of validity
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
     * @param int $checkValue Check value
     * @param int $repeat Repeat time
     *
     * @throws ArithmeticExpressionErrorException
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
     * @param int $result Check result
     * @param int $value Check value
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
        return Customization::getWording("_checkLevel", $checkLevel);
    }

    /**
     * Send private message.
     */
    private function sendPrivateMessage(): void
    {
        if ($this->chatType == "group")
            $privateReply = Customization::getReply("checkPrivatelyInGroup",
                APIService::getGroupInfo($this->chatId)["data"]["group_name"], $this->chatId);
        else
            $privateReply = Customization::getReply("checkPrivatelyInDiscuss",
                $this->chatId);

        $privateReply .= $this->reply;
        APIService::sendPrivateMessageAsync($this->userId, $privateReply);
    }
}
