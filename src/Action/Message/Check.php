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
        list($private, $bp, $item, $adjustments, $repetition) = $this->parseOrder();

        list($item, $value, $attributes) = $this->getCheckInfo($item);

        if (!$this->checkRange($value, $repetition)) {
            return;
        }

        $nickname = $this->getNickname();
        $checkDetails = $this->getCheckDetails($bp, $adjustments, $value, $repetition);

        if (empty($item)) {
            $reply = Convertor::toCustomString($this->config->getReply("checkResult"), [
                "昵称" => $nickname,
                "检定次数" => $repetition,
                "检定项目" => "",
                "检定详情" => $checkDetails
            ]);
        } else {
            $reply = Convertor::toCustomString($this->config->getReply("checkResultWithAttributes"), [
                "昵称" => $nickname,
                "检定次数" => $repetition,
                "检定项目" => $item,
                ...$attributes,
                "检定详情" => $checkDetails
            ]);
        }

        if ($private) {
            if ($this->message instanceof GroupMessage) {
                $this->sendPrivateMessageAsync(Convertor::toCustomString(
                    $this->config->getReply("checkPrivateResult"),
                    [
                        "群名" => $this->message->sender->group->name,
                        "群号" => $this->message->sender->group->id,
                        "检定详情" => $reply
                    ]
                ));

                $this->setReply("checkPrivate", [
                    "昵称" => $nickname,
                    "检定次数" => $repetition
                ]);
            } else {
                $this->setReply("checkPrivateNotInGroup");
            }
        } else {
            $this->setRawReply($reply);
        }
    }

    /**
     * @inheritDoc
     *
     * @return array Parsed elements.
     *
     * @throws OrderErrorException
     */
    protected function parseOrder(): array
    {
        if (!preg_match(
            "/^(h)?\s*([bp](?:\s*[1-9][0-9]*)?\s+)?([\x{4e00}-\x{9fa5}a-z0-9\s]+)\s*((?:[+-][1-9][0-9]*\s*)*)(?:#([1-9][0-9]*)?)?$/ui",
            $this->order,
            $matches
        )) {
            throw new OrderErrorException;
        }

        $private = !empty($matches[1]);
        $bp = $matches[2];
        $item = $matches[3];
        $adjustments = str_replace(" ", "", $matches[4] ?? "");
        $repetition = (int) ($matches[5] ?? 1);

        /**
         * @var bool $private Private check flag.
         * @var string $bp Bonus/Punishment check flag.
         * @var string $item Check item (skill/attribute name or value).
         * @var string $adjustments Adjustment operations.
         * @var int $repetition Repetition count.
         */
        return [$private, $bp, $item, $adjustments, $repetition];
    }

    /**
     * Get check information.
     *
     * @param string $item The item to be checked.
     *
     * @return array Real item and value to be checked (and attributes).
     *
     * @throws CharacterCardLostException|ItemNotExistException|NotBoundException
     */
    protected function getCheckInfo(string $item): array
    {
        if (is_numeric($item)) {
            return [null, (int) $item];
        } else {
            $card = $this->resource->getCharacterCard(
                $this->chatSettings->getCharacterCardId($this->message->sender->id)
            );

            try {
                $value = $card->getAttribute($item);
            } catch (ItemNotExistException $e) {
                $value = $card->getSkill($item);
            }

            return [
                $item,
                $value,
                [
                    "当前HP" => $card->getAttribute("HP"),
                    "当前MP" => $card->getAttribute("MP"),
                    "当前SAN" => $card->getAttribute("SAN"),
                    "最大HP" => intval(($card->getAttribute("SIZ") + $card->getAttribute("CON")) / 10),
                    "最大MP" => intval($card->getAttribute("POW") / 5),
                    "最大SAN" => 99 - $card->getSkill("克苏鲁神话")
                ]
            ];
        }
    }

    /**
     * Check the range.
     *
     * @param int $value The value to be checked.
     * @param int $repetition Repetition count.
     *
     * @return bool Validity.
     *
     * @throws RepeatTimeOverstepException
     */
    protected function checkRange(int $value, int $repetition): bool
    {
        if ($value < 1) {
            $this->setReply("checkValueInvalid");

            return false;
        } elseif ($repetition < 1 || $repetition > $this->config->getOrder("maxRepeatTimes")) {
            throw new RepeatTimeOverstepException();
        }

        return true;
    }

    /**
     * Get check details.
     *
     * @param string $bp B/P order.
     * @param string $adjustments Adjustment operations.
     * @param int $value The value to be checked.
     * @param int $repetition Repetition count.
     *
     * @return string Check details.
     *
     * @throws CheckRuleLostException|DangerousException|DiceNumberOverstepException|ExpressionErrorException
     * @throws ExpressionInvalidException|InvalidException|MatchFailedException|SurfaceNumberOverstepException
     */
    protected function getCheckDetails(string $bp, string $adjustments, int $value, int $repetition): string
    {
        $details = $repetition > 1 ? "\n" : "";
        $reply = $this->config->getReply("checkDetail");

        while ($repetition--) {
            $dice = isset($dice) ? clone $dice : new Dice("{$bp} D{$adjustments}", 100);

            // Adjust result
            $result = $dice->result < 1 ? 1 : $dice->result;
            $result = $result > 100 ? 100 : $result;

            $details .= Convertor::toCustomString($reply, [
                "掷骰结果" => $dice->getCompleteExpression(),
                "检定值" => $value,
                "检定结果" => $this->getCheckLevel($result, $value)
            ]);
            $details .= "\n";
        }

        return rtrim($details);
    }

    /**
     * Get check level according to the check rule.
     *
     * @param int $result Check result.
     * @param int $value The value to be checked.
     *
     * @return string Check level.
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
