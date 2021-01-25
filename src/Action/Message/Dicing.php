<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Dice;
use DiceRobot\Data\Report\Message\GroupMessage;
use DiceRobot\Exception\DiceException\{DiceNumberOverstepException, ExpressionErrorException,
    ExpressionInvalidException, SurfaceNumberOverstepException};
use DiceRobot\Exception\RepeatOverstepException;
use DiceRobot\Util\Convertor;

/**
 * Class Dicing
 *
 * Roll a dice determined by dicing expression.
 *
 * @order r
 *
 *      Sample: .rd
 *              .r 6D90
 *              .rh
 *              .rh (5D80K2+10)x5 Reason
 *              .rs
 *              .rs (D60+5)*2 Reason
 *              .rb
 *              .rb3 Reason
 *              .rp
 *              .rp5 Reason
 *              .rd#4
 *              .rh (8DK3+10)x5 Reason#3
 *              .rb#5
 *              .rp#2
 *
 * @package DiceRobot\Action\Message
 */
class Dicing extends MessageAction
{
    /**
     * @inheritDoc
     *
     * @throws DiceNumberOverstepException|ExpressionErrorException|ExpressionInvalidException
     * @throws RepeatOverstepException|SurfaceNumberOverstepException
     */
    public function __invoke(): void
    {
        list($expression, $repetition) = $this->parseOrder();

        $this->checkRange($repetition);

        list($vType, $reason, $detail) = $this->dicing($expression, $repetition);

        $reply = Convertor::toCustomString(
            $this->config->getReply(empty($reason) ? "dicingResult" : "dicingResultWithReason"),
            [
                "原因" => $reason,
                "昵称" => $this->getNickname(),
                "掷骰结果" => $detail
            ]
        );

        if ($vType === "H") {
            if ($this->message instanceof GroupMessage) {
                $this->sendPrivateMessageAsync(Convertor::toCustomString(
                    $this->config->getReply("dicingPrivateResult"),
                    [
                        "群名" => $this->message->sender->group->name,
                        "群号" => $this->message->sender->group->id,
                        "掷骰详情" => $reply
                    ]
                ));

                $this->setReply(empty($reason) ? "dicingPrivate" : "dicingPrivateWithReason", [
                    "原因" => $reason,
                    "昵称" => $this->getNickname(),
                    "掷骰次数" => $repetition
                ]);
            } else {
                $this->setReply("dicingPrivateNotInGroup");
            }
        } else {
            $this->setRawReply($reply);
        }
    }

    /**
     * @inheritDoc
     *
     * @return array Parsed elements.
     */
    protected function parseOrder(): array
    {
        preg_match("/^([\S\s]*?)(?:#([1-9][0-9]*))?$/", $this->order, $matches);
        $expression = $matches[1];
        $repetition = empty($matches[2]) ? 1 : (int) $matches[2];

        /**
         * @var string $expression Dicing expression.
         * @var int $repetition Count of repetition.
         */
        return [$expression, $repetition];
    }

    /**
     * Check the range.
     *
     * @param int $repetition Count of repetition.
     *
     * @throws RepeatOverstepException Count of repetition oversteps the limit.
     */
    protected function checkRange(int $repetition): void
    {
        if ($repetition < 1 || $repetition > $this->config->getOrder("maxRepeat")) {
            throw new RepeatOverstepException();
        }
    }

    /**
     * Execute dicing order.
     *
     * @param string $expression Dicing expression.
     * @param int $repetition Count of repetition.
     *
     * @return array Dicing reason and detail.
     *
     * @throws DiceNumberOverstepException|ExpressionErrorException|ExpressionInvalidException
     * @throws SurfaceNumberOverstepException
     */
    protected function dicing(string $expression, int $repetition): array
    {
        $detail = $repetition > 1 ? "\n" : "";
        /** @var Dice[] $dices */
        $dices = [];

        for ($i = 0; $i < $repetition; $i++) {
            $dices[$i] = isset($dices[$i - 1]) ?
                clone $dices[$i - 1] :
                new Dice($expression, $this->chatSettings->getInt("defaultSurfaceNumber"));
            $detail .= $dices[$i]->getCompleteExpression() . "\n";
        }

        // Simplify the reply
        if (mb_strlen($detail) > $this->config->getOrder("maxReplyCharacter")) {
            $detail = "";

            for ($i = 0; $i < $repetition; $i++) {
                $detail .= $dices[$i]->getCompleteExpression(true) . "\n";
            }
        }

        return [$dices[0]->vType ?? null, $dices[0]->reason ?? "", rtrim($detail)];
    }
}
