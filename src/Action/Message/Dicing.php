<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Dice;
use DiceRobot\Data\Report\Message\GroupMessage;
use DiceRobot\Exception\RepeatTimeOverstepException;
use DiceRobot\Exception\DiceException\{DiceNumberOverstepException, ExpressionErrorException,
    ExpressionInvalidException, SurfaceNumberOverstepException};
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
     * @throws RepeatTimeOverstepException|SurfaceNumberOverstepException
     */
    public function __invoke(): void
    {
        list($expression, $repeat) = $this->parseOrder();

        $this->checkRange($repeat);

        list($vType, $reason, $detail) = $this->dicing($expression, $repeat);

        $this->reply = trim(
            ($reasonHeading = ($reason == "") ? "" :
                Convertor::toCustomString(
                    $this->config->getString("reply.dicingReason"),
                    [
                        "原因" => $reason
                    ]
                )
            ).
            Convertor::toCustomString(
                $this->config->getString("reply.dicingResult"),
                [
                    "昵称" => $this->getNickname()
                ]
            ) .
            ($repeat > 1 ? "\n" : "") .
            $detail
        );

        if ($vType === "H")
        {
            if ($this->message instanceof GroupMessage)
            {
                $this->sendPrivateMessage(
                    Convertor::toCustomString(
                        $this->config->getString("reply.dicingPrivatelyHeading"),
                        [
                            "群名" => $this->message->sender->group->name,
                            "群号" => $this->message->sender->group->id
                        ]
                    ) . $this->reply
                );
                $this->reply = $reasonHeading .
                    Convertor::toCustomString(
                        $this->config->getString("reply.dicingPrivately"),
                        [
                            "昵称" => $this->getNickname(),
                            "掷骰次数" => $repeat
                        ]
                    );
            }
            else
                $this->reply = $this->config->getString("reply.dicingPrivatelyNotInGroup");
        }
    }

    /**
     * @inheritDoc
     *
     * @return array Parsed elements
     */
    protected function parseOrder(): array
    {
        preg_match("/^([\S\s]*?)(?:#([1-9][0-9]*))?$/", $this->order, $matches);
        /** @var string $expression */
        $expression = $matches[1];
        /** @var int $repeat */
        $repeat = empty($matches[2]) ? 1 : (int) $matches[2];

        return [$expression, $repeat];
    }

    /**
     * Check the range.
     *
     * @param int $repeat Repeat count
     *
     * @throws RepeatTimeOverstepException
     */
    protected function checkRange(int $repeat): void
    {
        if ($repeat < 1 || $repeat > $this->config->getInt("order.maxRepeatTimes"))
            throw new RepeatTimeOverstepException();
    }

    /**
     * Execute dicing order.
     *
     * @param string $expression Dicing expression
     * @param int $repeat Repeat count
     *
     * @return array Dicing reason and detail
     *
     * @throws DiceNumberOverstepException|ExpressionErrorException|ExpressionInvalidException
     * @throws SurfaceNumberOverstepException
     */
    protected function dicing(string $expression, int $repeat): array
    {
        $detail = "";

        while ($repeat--)
        {
            $dice = isset($dice) ?
                clone $dice : new Dice($expression, $this->chatSettings->getInt("defaultSurfaceNumber"));
            $detail .= $dice->getCompleteExpression() . "\n";
        }

        return [$dice->vType ?? NULL, $dice->reason ?? "", $detail];
    }
}
