<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Dice;
use DiceRobot\Data\Response\SanityCheckResponse;
use DiceRobot\Exception\CharacterCardException\{ItemNotExistException, LostException, NotBoundException};
use DiceRobot\Exception\DiceException\{DiceNumberOverstepException, ExpressionErrorException,
    ExpressionInvalidException, SurfaceNumberOverstepException};
use DiceRobot\Exception\OrderErrorException;

/**
 * Class SanityCheck
 *
 * Sanity check.
 *
 * @order sc
 *
 *      Sample: .sc 2/5
 *              .sc 0/2D5
 *              .sc 2D3/3D6
 *              .sc 1/8 50
 *              .sc 0/4D3 60
 *              .sc 3D4/5D10 90
 *
 * @package DiceRobot\Action\Message
 */
class SanityCheck extends MessageAction
{
    /**
     * @inheritDoc
     *
     * @throws DiceNumberOverstepException|ExpressionErrorException|ExpressionInvalidException
     * @throws ItemNotExistException|LostException|NotBoundException|OrderErrorException
     * @throws SurfaceNumberOverstepException
     */
    public function __invoke(): void
    {
        list($successExpression, $failureExpression, $sanity) = $this->parseOrder();

        list($successDecrease, $failureDecrease) = $this->getDecreases($successExpression, $failureExpression);

        if (!$this->checkRange($successDecrease, $failureDecrease)) {
            return;
        }

        list($checkResult, $fullCheckResult) = $this->getCheckResult();

        if (is_null($sanity)) {
            list($checkLevel, $decrease, $previousSanity, $currentSanity, $maxSanity) =
                $this->check($sanity, $successDecrease, $failureDecrease, $checkResult);

            $this->setReply("sanityCheckResult", [
                "昵称" => $this->getNickname(),
                "掷骰结果" => $fullCheckResult,
                "检定结果" => $this->config->getString("wording.sanityCheckLevel.$checkLevel"),
                "SAN值减少" => $decrease,
                "原有SAN值" => $previousSanity,
                "当前SAN值" => $currentSanity,
                "最大SAN值" => $maxSanity
            ]);
        } else {
            list($checkLevel, $decrease) = $this->check($sanity, $successDecrease, $failureDecrease, $checkResult);

            $this->setReply("sanityCheckResultWithSanity", [
                "昵称" => $this->getNickname(),
                "掷骰结果" => $fullCheckResult,
                "检定结果" => $this->config->getString("wording.sanityCheckLevel.$checkLevel"),
                "SAN值减少" => $decrease,
                "原有SAN值" => $sanity,
                "当前SAN值" => $sanity - $decrease
            ]);
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
            "/^([0-9DK+\-x*()（）]+)\s*\/\s*([0-9DK+\-x*()（）]+)(?:\s+(-?[1-9][0-9]*))?$/i",
            $this->order,
            $matches
        )) {
            throw new OrderErrorException;
        }

        $successExpression = $matches[1];
        $failureExpression = $matches[2];
        $sanity = empty($matches[3]) ? null : (int) $matches[3];

        /**
         * @var string $successExpression Expression when check succeeded.
         * @var string $failureExpression Expression when check failed.
         * @var int|null $sanity Sanity to check.
         */
        return [$successExpression, $failureExpression, $sanity];
    }

    /**
     * Get decreases of sanity.
     *
     * @param string $successExpression Expression when checking successful.
     * @param string $failureExpression Expression when checking failed.
     *
     * @return array Decreases.
     *
     * @throws DiceNumberOverstepException|ExpressionErrorException|ExpressionInvalidException
     * @throws SurfaceNumberOverstepException
     */
    protected function getDecreases(string $successExpression, string $failureExpression): array
    {
        $successDecrease = (new Dice($successExpression))->result;
        $failureDecrease = (new Dice($failureExpression))->result;

        return [$successDecrease, $failureDecrease];
    }

    /**
     * Check the range.
     *
     * @param int $successDecrease Decrease when checking successful.
     * @param int $failureDecrease Decrease when checking failed.
     *
     * @return bool Validity.
     */
    protected function checkRange(int $successDecrease, int $failureDecrease): bool
    {
        if ($successDecrease < 0 || $failureDecrease < 0) {
            $this->setReply("sanityCheckWrongExpression");

            return false;
        }

        return true;
    }

    /**
     * Get check result and complete expression.
     *
     * @return array Check result and complete expression.
     *
     * @throws DiceNumberOverstepException|ExpressionErrorException|ExpressionInvalidException
     * @throws SurfaceNumberOverstepException
     */
    protected function getCheckResult(): array
    {
        $dice = new Dice("D", 100);

        $checkResult = $dice->result;
        $fullCheckResult = $dice->getCompleteExpression();

        return [$checkResult, $fullCheckResult];
    }

    /**
     * Check sanity.
     *
     * @param int|null $sanity Sanity.
     * @param int $successDecrease Decrease when checking successful.
     * @param int $failureDecrease Decrease when checking failed.
     * @param int $checkResult Check result.
     *
     * @return array Check details.
     *
     * @throws ItemNotExistException
     * @throws LostException
     * @throws NotBoundException
     */
    protected function check(?int $sanity, int $successDecrease, int $failureDecrease, int $checkResult): array
    {
        if (is_null($sanity)) {
            // Online sanity check
            $cardId = $this->chatSettings->getCharacterCardId($this->message->sender->id);
            $card = $this->resource->getCharacterCard($cardId);

            $response = $this->updateCard($cardId, $checkResult, [$successDecrease, $failureDecrease]);

            $card->setAttribute("SAN", $response->afterSanity);

            $checkLevel = $response->checkSuccess ? "success" : "failure";
            $decrease = $response->checkSuccess ? $successDecrease : $failureDecrease;
            $previousSanity = $response->beforeSanity;
            $currentSanity = $response->afterSanity;
            $maxSanity = 99 - ($card->getSkill("克苏鲁神话") ?? 0);

            return [$checkLevel, $decrease, $previousSanity, $currentSanity, $maxSanity];
        } else {
            // Offline sanity check
            $checkSuccess = $checkResult <= $sanity;

            $checkLevel = $checkSuccess ? "success" : "failure";
            $decrease = $checkSuccess ? $successDecrease : $failureDecrease;

            return [$checkLevel, $decrease];
        }
    }

    /**
     * Update the character card.
     *
     * @param int $cardId Character card ID.
     * @param int $checkResult The check result.
     * @param array $decreases Decreases.
     *
     * @return SanityCheckResponse The response.
     */
    protected function updateCard(int $cardId, int $checkResult, array $decreases): SanityCheckResponse
    {
        return $this->api->sanityCheck(
            $cardId,
            $checkResult,
            $decreases,
            $this->api->authorize($this->robot->getId(), $this->message->sender->id)->token
        );
    }
}
