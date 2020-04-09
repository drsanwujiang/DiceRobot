<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Exception\InformativeException\APIException\UnexpectedErrorException;
use DiceRobot\Exception\InformativeException\CharacterCardException\NotBoundException;
use DiceRobot\Exception\InformativeException\DiceException\DiceNumberOverstepException;
use DiceRobot\Exception\InformativeException\DiceException\ExpressionErrorException;
use DiceRobot\Exception\InformativeException\DiceException\SurfaceNumberOverstepException;
use DiceRobot\Exception\InformativeException\IOException\FileLostException;
use DiceRobot\Exception\InformativeException\IOException\FileDecodeException;
use DiceRobot\Exception\InformativeException\IOException\FileUnwritableException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;
use DiceRobot\Exception\InformativeException\OrderErrorException;
use DiceRobot\Service\API\Response\UpdateCardResponse;
use DiceRobot\Service\Container\CharacterCard;
use DiceRobot\Service\Container\Dice\Dice;
use DiceRobot\Service\Customization;

/**
 * Change investigator's attributes (HP, MP or SAN).
 */
final class ChangeAttribute extends Action
{
    /**
     * @throws DiceNumberOverstepException
     * @throws ExpressionErrorException
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws FileUnwritableException
     * @throws InformativeException
     * @throws InternalErrorException
     * @throws JSONDecodeException
     * @throws NetworkErrorException
     * @throws NotBoundException
     * @throws OrderErrorException
     * @throws SurfaceNumberOverstepException
     * @throws UnexpectedErrorException
     */
    public function __invoke(): void
    {
        $order = preg_replace("/^\./", "", $this->message);
        $this->checkOrder($order);

        // Parse the order
        preg_match("/^(hp|mp|san)[\s]*([+-])[\s]*([\S]+)$/i", $order, $matches);
        $attribute = strtoupper($matches[1]);
        $symbol = $matches[2];
        $expression = $matches[3];

        if (is_numeric($expression))
        {
            $value = (int) $expression;
            $result = $expression;

            if ($value < 0 || $value > Customization::getSetting("maxAttributeChange"))
                throw new InformativeException("changeAttributeValueOverstep");
        }
        else
        {
            $dice = new Dice($expression);

            if ($dice->reason != "")
                throw new InformativeException("changeAttributeWrongExpression");

            $value = $dice->rollResult;
            $result = $dice->getCompleteExpression();
        }

        $cardId = $this->chatSettings->getCharacterCardId($this->userId);
        $card = new CharacterCard($cardId);

        $response = $this->updateCard($cardId, $attribute, (int) ($symbol . $value));

        $card->set($attribute, $response->afterValue);  // Update item in the character card

        $this->reply = Customization::getReply("changeAttributeResult", $this->userNickname,
            $attribute, Customization::getWording("attributeChange", $symbol == "+"),
            $result, $attribute, $response->afterValue);
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
        if (!preg_match("/^(hp|mp|san)[\s]*[+-][\s]*[\S]+/i", $order))
            throw new OrderErrorException;
    }

    /**
     * Update character card.
     *
     * @param int $cardId Character card ID
     * @param string $attribute Attribute name
     * @param int $change The change value
     *
     * @return UpdateCardResponse The response
     *
     * @throws InternalErrorException
     * @throws JSONDecodeException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    private function updateCard(int $cardId, string $attribute, int $change): UpdateCardResponse
    {
        $this->apiService->auth($this->selfId, $this->userId);

        return $this->apiService->updateCard($cardId, $attribute, $change);
    }
}
