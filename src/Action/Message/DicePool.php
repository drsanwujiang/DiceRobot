<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Dice;
use DiceRobot\Exception\OrderErrorException;
use DiceRobot\Exception\DiceException\{DiceNumberOverstepException, ExpressionErrorException,
    ExpressionInvalidException, SurfaceNumberOverstepException};
use DiceRobot\Util\Convertor;

/**
 * Class DicePool
 *
 * Dice pool.
 *
 * @order w
 *
 *      Sample: .ww
 *              .ww 10a5 Reason
 *              .w Reason
 *              .w 5a10
 *
 * @package DiceRobot\Action\Message
 */
class DicePool extends MessageAction
{
    /**
     * @inheritDoc
     *
     * @throws DiceNumberOverstepException|ExpressionErrorException|ExpressionInvalidException|OrderErrorException
     * @throws SurfaceNumberOverstepException
     */
    public function __invoke(): void
    {
        list($detailed, $diceNumber, $threshold, $reason) = $this->parseOrder();

        if (!$this->checkRange($threshold)) {
            return;
        }

        list($finalResult, $details) = $this->dicing($diceNumber, $threshold);

        $this->reply =
            ($reason == "" ? "" :
                Convertor::toCustomString(
                    $this->config->getString("reply.dicePoolReason"),
                    [
                        "原因" => $reason
                    ]
                )
            ) .
            Convertor::toCustomString(
                $this->config->getString("reply.dicePoolResult"),
                [
                    "昵称" => $this->getNickname()
                ]
            ) .
            "{$diceNumber}a{$threshold}=" .
            ($detailed ? "{$details}=" : "") .
            $finalResult;
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
            "/^(w?)\s*(?:([1-9][0-9]*)?\s*a\s*([1-9][0-9]*)?)?\s*([\S\s]*)$/i",
            $this->order,
            $matches
        )) {
            throw new OrderErrorException;
        }

        $detailed = !empty($matches[1]);
        $diceNumber = empty($matches[2]) ? 10 : (int) $matches[2];
        $threshold = empty($matches[3]) ? 10 : (int) $matches[3];
        $reason = $matches[4];

        /**
         * @var bool $detailed Detailed process flag
         * @var int $diceNumber Dice number
         * @var int $threshold Threshold of result
         * @var string $reason Reason
         */
        return [$detailed, $diceNumber, $threshold, $reason];
    }

    /**
     * Check the range.
     *
     * @param int $threshold The threshold
     *
     * @return bool Validity
     */
    protected function checkRange(int $threshold): bool
    {
        if ($threshold < 5 || $threshold > 10) {
            $this->reply = $this->reply = $this->config->getString("reply.dicePoolThresholdOverstep");

            return false;
        }

        return true;
    }

    /**
     * Execute dicing order.
     *
     * @param int $diceNumber Dice number
     * @param int $threshold The threshold
     *
     * @return array Final result and details
     *
     * @throws DiceNumberOverstepException|ExpressionErrorException|ExpressionInvalidException
     * @throws SurfaceNumberOverstepException
     */
    protected function dicing(int $diceNumber, int $threshold): array
    {
        $finalResult = 0;  // The number of results greater than 8
        $details = [];

        while ($diceNumber) {
            $dice = isset($dice) ? clone $dice : new Dice("{$diceNumber}D", 10);
            $diceNumber = 0;

            foreach ($dice->subexpressions[0]->results as $result) {
                if ($result >= $threshold) {
                    $diceNumber++;
                }

                if ($result >= 8) {
                    $finalResult++;
                }
            }

            $details[] = $dice->subexpressions[0]->getResultString(",");
        }

        return [$finalResult, join("+", $details)];
    }
}
