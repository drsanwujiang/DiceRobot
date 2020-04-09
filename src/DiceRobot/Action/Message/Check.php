<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException;
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
use DiceRobot\Exception\InformativeException\IOException\FileUnwritableException;
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
    private CheckRule $checkRule;

    /**
     * The constructor.
     *
     * @param object $eventData The event data
     *
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws FileUnwritableException
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
     * @throws InformativeException
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
        $order = preg_replace("/^\.ra[\s]*/i", "", $this->message);
        $this->checkOrder($order);

        // Parse the order
        preg_match("/^(h)?[\s]*([bp](?:[\s]*[1-9][0-9]*)?[\s]+)?([\x{4e00}-\x{9fa5}a-z]+|[1-9][0-9]*)[\s]*((?:[+-][1-9][0-9]*[\s]*)*)(?:#([1-9][0-9]*)?)?$/ui", $order, $matches);
        $private = strtoupper($matches[1]) == "H";
        $bp = $matches[2];
        $item = $matches[3];
        $addition = str_replace(" ", "", $matches[4]);
        $repeat = (int) ($matches[5] ?? 1);

        $checkValue = $this->getCheckValue($item, $repeat);
        $this->reply .= $repeat > 1 ? "\n" : "";

        $this->checkRange($checkValue, $repeat);
        $this->check($bp, $addition, $checkValue, $repeat);

        if ($private && $this->chatType == "private")
            $this->reply = Customization::getReply("checkPrivatelyInPrivate");
        elseif ($private)
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
        if (!preg_match("/^(h)?[\s]*([bp]([\s]*[1-9][0-9]*)?[\s]+)?([\x{4e00}-\x{9fa5}a-z]+|[1-9][0-9]*)[\s]*([+-][1-9][0-9]*[\s]*)*(#([1-9][0-9]*)?)?$/ui", $order))
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
        if (is_numeric($item))
        {
            $checkValue = (int) $item;
            $this->reply = Customization::getReply("checkResultHeading",
                $this->userNickname, $repeat, "");
        }
        else
        {
            $card = new CharacterCard($this->chatSettings->getCharacterCardId($this->userId));
            $checkValue = $card->get(strtoupper($item));
            $this->reply = Customization::getReply("checkResultHeadingWithAttributes",
                $this->userNickname,
                $card->get("HP"), intval(($card->get("SIZ") + $card->get("CON")) / 10),
                $card->get("MP"), intval($card->get("POW") / 5),
                $card->get("SAN"), 99 - $card->get("克苏鲁神话"),
                $repeat, $item);
        }

        return $checkValue;
    }

    /**
     * Check range of the value and repeat time.
     *
     * @param int $value The value
     * @param int $repeat Repeat time
     *
     * @throws InformativeException
     * @throws RepeatTimeOverstepException
     */
    private function checkRange(int $value, int $repeat): void
    {
        if ($value < 1)
            throw new InformativeException("checkValueInvalid");
        elseif ($value > Customization::getSetting("maxAttribute"))
            throw new InformativeException("checkValueTooLarge");
        elseif ($repeat < 1 || $repeat > Customization::getSetting("maxRepeatTimes"))
            throw new RepeatTimeOverstepException();
    }

    /**
     * Check.
     *
     * @param string $bp B/P order
     * @param string $addition Additional operations
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
    private function check(string $bp, string $addition, int $checkValue, int $repeat): void
    {
        do
        {
            $dice = new Dice(trim("{$bp}D100{$addition}"));
            $dice->rollResult = $dice->rollResult < 1 ? 1 : $dice->rollResult;
            $dice->rollResult = $dice->rollResult > 100 ? 100 : $dice->rollResult;
            $this->reply .= Customization::getReply("checkResult", $dice->getCompleteExpression(),
                    $checkValue, $this->getCheckLevel($dice->rollResult, $checkValue)) . "\n";
        } while (--$repeat);

        $this->reply = trim($this->reply);
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
