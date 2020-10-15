<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Dice;
use DiceRobot\Data\Report\Message\GroupMessage;
use DiceRobot\Exception\{OrderErrorException, RepeatTimeOverstepException};
use DiceRobot\Exception\CharacterCardException\{ItemNotExistException, LostException as CharacterCardLostException,
    NotBoundException};
use DiceRobot\Exception\CheckRuleException\{DangerousException, InvalidException,
    LostException as CheckRuleLostException, MatchFailedException};
use DiceRobot\Exception\DiceException\{DiceNumberOverstepException, ExpressionErrorException,
    ExpressionInvalidException, SurfaceNumberOverstepException};
use DiceRobot\Util\Convertor;

/**
 * Class Check
 *
 * Check investigator's attribute/skill.
 *
 * @order ra
 *
 *      Sample: .ra 80
 *              .ra STR#3
 *              .rab 70-10#2
 *              .rap 3 DEX
 *              .rah 60
 *              .rah Medicine+10#2
 *              .rahb 2 50
 *              .rahp MySkill#3
 *
 * @package DiceRobot\Action\Message
 */
class Check extends MessageAction
{
    /**
     * @inheritDoc
     *
     * @throws CharacterCardLostException|CheckRuleLostException|DangerousException|DiceNumberOverstepException
     * @throws ExpressionErrorException|ExpressionInvalidException|InvalidException|ItemNotExistException
     * @throws MatchFailedException|NotBoundException|OrderErrorException|RepeatTimeOverstepException
     * @throws SurfaceNumberOverstepException|
     */
    public function __invoke(): void
    {
        list($private, $bp, $item, $addition, $repeat) = $this->parseOrder();

        list($checkValue, $heading) = $this->getCheckValue($item, $repeat);

        if (!$this->checkRange($checkValue, $repeat))
            return;

        $this->reply = trim(
            $heading .
            ($repeat > 1 ? "\n" : "") .
            $this->check($bp, $addition, $checkValue, $repeat)
        );

        if ($private)
        {
            if (!($this->message instanceof GroupMessage))
            {
                $this->sendPrivateMessage(
                    Convertor::toCustomString(
                        $this->config->getString("reply.checkPrivatelyHeading"),
                        [
                            "群名" => $this->message->sender->group->name,
                            "群号" => $this->message->sender->group->id
                        ]
                    ) . $this->reply
                );
                $this->reply =
                    Convertor::toCustomString(
                        $this->config->getString("reply.checkPrivately"),
                        [
                            "昵称" => $this->getNickname(),
                            "检定次数" => $repeat
                        ]
                    );
            }
            else
                $this->reply = $this->config->getString("reply.checkPrivatelyNotInGroup");
        }
    }

    /**
     * @inheritDoc
     *
     * @return array Parsed elements
     *
     * @throws OrderErrorException
     */
    protected function parseOrder(): array
    {
        if (!preg_match(
            "/^(h)?\s*([bp](?:\s*[1-9][0-9]*)?\s+)?([\x{4e00}-\x{9fa5}a-z0-9\s]+)\s*((?:[+-][1-9][0-9]*\s*)*)(?:#([1-9][0-9]*)?)?$/ui",
            $this->order,
            $matches
        ))
            throw new OrderErrorException;

        /** @var bool $private */
        $private = !empty($matches[1]);
        /** @var string $bp */
        $bp = $matches[2];
        /** @var string $item */
        $item = $matches[3];
        /** @var string $addition */
        $addition = str_replace(" ", "", $matches[4] ?? "");
        /** @var int $repeat */
        $repeat = (int) ($matches[5] ?? 1);

        return [$private, $bp, $item, $addition, $repeat];
    }

    /**
     * Get the value to be checked and the result heading.
     *
     * @param string $item The item
     * @param int $repeat Repeat count
     *
     * @return array The value to be checked and the result heading
     *
     * @throws CharacterCardLostException|ItemNotExistException|NotBoundException
     */
    protected function getCheckValue(string $item, int $repeat): array
    {
        if (is_numeric($item))
        {
            $checkValue = (int) $item;
            $heading =
                Convertor::toCustomString(
                    $this->config->getString("reply.checkResultHeading"),
                    [
                        "昵称" => $this->getNickname(),
                        "检定次数" => $repeat,
                        "检定项目" => ""
                    ]
                );
        }
        else
        {
            $card = $this->resource->getCharacterCard(
                $this->chatSettings->getCharacterCardId($this->message->sender->id)
            );

            try
            {
                $checkValue = $card->getAttribute($item);
            }
            catch (ItemNotExistException $e)
            {
                $checkValue = $card->getSkill($item);
            }

            $heading =
                Convertor::toCustomString(
                    $this->config->getString("reply.checkResultHeadingWithAttributes"),
                    [
                        "昵称" => $this->getNickname(),
                        "检定次数" => $repeat,
                        "检定项目" => $item,
                        "当前HP" => $card->getAttribute("HP"),
                        "当前MP" => $card->getAttribute("MP"),
                        "当前SAN" => $card->getAttribute("SAN"),
                        "最大HP" => intval(($card->getAttribute("SIZ") + $card->getAttribute("CON")) / 10),
                        "最大MP" => intval($card->getAttribute("POW") / 5),
                        "最大SAN" => 99 - $card->getSkill("克苏鲁神话"),
                    ]
                );
        }

        return [$checkValue, $heading];
    }

    /**
     * Check the range.
     *
     * @param int $checkValue The value to be checked
     * @param int $repeat Repeat count
     *
     * @return bool Validity
     *
     * @throws RepeatTimeOverstepException
     */
    protected function checkRange(int $checkValue, int $repeat): bool
    {
        if ($checkValue < 1)
        {
            $this->reply = $this->config->getString("reply.checkValueInvalid");

            return false;
        }
        elseif ($repeat < 1 || $repeat > $this->config->getInt("order.maxRepeatTimes"))
            throw new RepeatTimeOverstepException();

        return true;
    }

    /**
     * Check attribute/skill.
     *
     * @param string $bp B/P order
     * @param string $addition Additional operations
     * @param int $checkValue The check value
     * @param int $repeat Repeat time
     *
     * @return string Check detail.
     *
     * @throws CheckRuleLostException|DangerousException|DiceNumberOverstepException|ExpressionErrorException
     * @throws ExpressionInvalidException|InvalidException|MatchFailedException|SurfaceNumberOverstepException
     */
    protected function check(string $bp, string $addition, int $checkValue, int $repeat): string
    {
        $detail = "";

        while ($repeat--)
        {
            $dice = isset($dice) ? clone $dice : new Dice("{$bp} D{$addition}", 100);

            // Adjust result
            $result = $dice->result < 1 ? 1 : $dice->result;
            $result = $result > 100 ? 100 : $result;

            $detail .=
                Convertor::toCustomString(
                    $this->config->getString("reply.checkResult"),
                    [
                        "掷骰结果" => $dice->getCompleteExpression(),
                        "检定值" => $checkValue,
                        "检定结果" => $this->getCheckLevel($result, $checkValue)
                    ]
                ) . "\n";
        }

        return $detail;
    }

    /**
     * Get check level according to check rule.
     *
     * @param int $result Check result
     * @param int $value Check value
     *
     * @return string Check level
     *
     * @throws CheckRuleLostException|DangerousException|InvalidException|MatchFailedException
     */
    protected function getCheckLevel(int $result, int $value): string
    {
        $checkLevel = $this->resource
            ->getCheckRule($this->chatSettings->getInt("cocCheckRule"))
            ->getCheckLevel($result, $value);

        return $this->config->getString("wording.checkLevel.{$checkLevel}");
    }
}
