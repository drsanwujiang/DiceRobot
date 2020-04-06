<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Exception\InformativeException\APIException\UnexpectedErrorException;
use DiceRobot\Exception\InformativeException\CharacterCardException\ItemNotExistException;
use DiceRobot\Exception\InformativeException\CharacterCardException\NotBoundException;
use DiceRobot\Exception\InformativeException\DiceException\DiceNumberOverstepException;
use DiceRobot\Exception\InformativeException\DiceException\ExpressionErrorException;
use DiceRobot\Exception\InformativeException\DiceException\SurfaceNumberOverstepException;
use DiceRobot\Exception\InformativeException\IOException\FileDecodeException;
use DiceRobot\Exception\InformativeException\IOException\FileLostException;
use DiceRobot\Exception\InformativeException\IOException\FileUnwritableException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;
use DiceRobot\Exception\InformativeException\OrderErrorException;
use DiceRobot\Service\API\Response\SanityCheckResponse;
use DiceRobot\Service\Container\CharacterCard;
use DiceRobot\Service\Container\Dice\Dice;
use DiceRobot\Service\Customization;

/**
 * Sanity check.
 */
final class SanCheck extends Action
{
    /**
     * @throws DiceNumberOverstepException
     * @throws ExpressionErrorException
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws FileUnwritableException
     * @throws InternalErrorException
     * @throws ItemNotExistException
     * @throws JSONDecodeException
     * @throws NetworkErrorException
     * @throws NotBoundException
     * @throws OrderErrorException
     * @throws SurfaceNumberOverstepException
     * @throws UnexpectedErrorException
     */
    public function __invoke(): void
    {
        $order = preg_replace("/^\.sc[\s]*/i", "", $this->message, 1);
        $this->checkOrder($order);

        $cardId = $this->chatSettings->getCharacterCardId($this->userId);
        $characterCard = new CharacterCard($cardId);

        $orders = explode("/", $order, 2);
        $dices = $decreases = $decreaseResults = [];

        for ($i = 0; $i <= 1; $i++)
        {
            if (is_numeric($orders[$i]))
            {
                $decreases[$i] = (int) $orders[$i];
                $decreaseResults[$i] = $orders[$i];

                if (!$this->checkRange($decreases[$i]))
                    return;

                continue;
            }

            $dices[$i] = new Dice(trim($orders[$i]));

            if ($dices[$i]->reason != "")
            {
                $this->reply = Customization::getReply("sanCheckWrongExpression");
                return;
            }

            $decreases[$i] = $dices[$i]->rollResult;
            /** @noinspection PhpUndefinedMethodInspection */
            $decreaseResults[$i] = $dices[$i]->getCompleteExpression();
        }

        $dices[2] = new Dice(); // Check dice
        $checkResult = $dices[2]->rollResult;
        $response = $this->updateCard($cardId, $checkResult, $decreases);

        $maxSanity = 99 - $characterCard->get("克苏鲁神话") ?? 0;
        $checkResultString = $dices[2]->getCompleteExpression();
        $decreaseResult = $response->checkSuccess ? $decreaseResults[0] : $decreaseResults[1];

        $characterCard->set("SAN", $response->afterSanity);
        $this->reply = Customization::getReply("sanCheckResult", $this->userNickname, $checkResultString,
            $response->beforeSanity, Customization::getWording("sanCheckLevel", $response->checkSuccess),
            $decreaseResult, $response->afterSanity, $maxSanity);
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
        if (!preg_match("/^[\S]+\/[\S]+$/i", $order))
            throw new OrderErrorException;
    }

    /**
     * Check range of the value.
     *
     * @param int $value The value
     *
     * @return bool Validity
     */
    private function checkRange(int $value): bool
    {
        if ($value > Customization::getSetting("maxAttributeChange"))
        {
            $this->reply = Customization::getReply("sanCheckValueOverstep");
            return false;
        }
        elseif ($value < 0)
        {
            $this->reply = Customization::getReply("sanCheckWrongExpression");
            return false;
        }

        return true;
    }

    /**
     * Update the character card.
     *
     * @param int $cardId Character card ID
     * @param int $checkResult The check result
     * @param array $decreases Decreases
     *
     * @return SanityCheckResponse The response
     *
     * @throws InternalErrorException
     * @throws JSONDecodeException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    private function updateCard(int $cardId, int $checkResult, array $decreases): SanityCheckResponse
    {
        $this->apiService->auth($this->selfId, $this->userId);
        return $this->apiService->sc($cardId, $checkResult, $decreases);
    }
}
