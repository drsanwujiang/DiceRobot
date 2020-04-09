<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException;
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
     * @throws InformativeException
     * @throws InternalErrorException
     * @throws ItemNotExistException
     * @throws JSONDecodeException
     * @throws NetworkErrorException
     * @throws NotBoundException
     * @throws OrderErrorException
     * @throws SurfaceNumberOverstepException
     * @throws UnexpectedErrorException
     *
     * @noinspection PhpUndefinedMethodInspection
     */
    public function __invoke(): void
    {
        $order = preg_replace("/^\.sc[\s]*/i", "", $this->message);
        $this->checkOrder($order);

        // Parse the order
        preg_match("/^([0-9dk+\-x*()（）]+)[\s]*\/[\s]*([0-9dk+\-x*()（）]+)(?:[\s]+(-?[1-9][0-9]*))?$/i", $order, $matches);
        $expressions = array_slice($matches, 1, 2);
        $sanity = empty($matches[3] ?? "") ? NULL : (int) $matches[3];
        $dices = $decreases = $decreaseResults = [];

        // Parse the two decrease expressions
        for ($i = 0; $i <= 1; $i++)
        {
            if (is_numeric($expressions[$i]))
            {
                $decreases[$i] = (int) $expressions[$i];
                $decreaseResults[$i] = $expressions[$i];
            }
            else
            {
                $dices[$i] = new Dice(trim($expressions[$i]));

                if ($dices[$i]->reason != "")
                    throw new InformativeException("sanCheckWrongExpression");

                $decreases[$i] = $dices[$i]->rollResult;
                $decreaseResults[$i] = $dices[$i]->getCompleteExpression();
            }
        }

        $this->checkRange($decreases, $sanity ?? 0);

        $dices[2] = new Dice(); // Check dice
        $checkResult = $dices[2]->rollResult;
        $checkResultString = $dices[2]->getCompleteExpression();

        // Online sanity check or offline sanity check
        if (is_null($sanity))
        {
            $cardId = $this->chatSettings->getCharacterCardId($this->userId);
            $card = new CharacterCard($cardId);

            $response = $this->updateCard($cardId, $checkResult, $decreases);

            $decreaseResult = $response->checkSuccess ? $decreaseResults[0] : $decreaseResults[1];
            $maxSanity = 99 - ($card->get("克苏鲁神话") ?? 0);

            $card->set("SAN", $response->afterSanity);

            $this->reply = Customization::getReply("sanCheckResult", $this->userNickname, $checkResultString,
                $response->beforeSanity, Customization::getWording("sanCheckLevel", $response->checkSuccess),
                $decreaseResult, $response->afterSanity, $maxSanity);
        }
        else
        {
            $checkSuccess = $checkResult <= $sanity;
            $decrease = $checkSuccess ? $decreases[0] : $decreases[1];
            $decreaseResult = $checkSuccess ? $decreaseResults[0] : $decreaseResults[1];

            $this->reply = Customization::getReply("sanCheckResultWithSanity", $this->userNickname,
                $checkResultString, $sanity, Customization::getWording("sanCheckLevel", $checkSuccess),
                $decreaseResult, $sanity - $decrease);
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
        if (!preg_match("/^[0-9dk+\-x*()（）]+[\s]*\/[\s]*[0-9dk+\-x*()（）]+([\s]+-?[1-9][0-9]*)?$/i", $order))
            throw new OrderErrorException;
    }

    /**
     * Check range of the sanity.
     *
     * @param array $decreases The decreases
     * @param int $sanity The sanity
     *
     * @throws InformativeException
     */
    private function checkRange(array $decreases, int $sanity): void
    {
        if ($decreases[0] > Customization::getSetting("maxAttributeChange") ||
            $decreases[1] > Customization::getSetting("maxAttributeChange")
        )
            throw new InformativeException("sanCheckDecreaseOverstep");
        elseif ($decreases[0] < 0 || $decreases[1] < 0)
            throw new InformativeException("sanCheckWrongExpression");
        elseif ($sanity > Customization::getSetting("maxAttribute"))
            throw new InformativeException("sanCheckSanityOverstep");
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
        return $this->apiService->sanityCheck($cardId, $checkResult, $decreases);
    }
}
