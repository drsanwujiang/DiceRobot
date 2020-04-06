<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action;
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
        $order = preg_replace("/^\./", "", $this->message, 1);
        $this->checkOrder($order);
        $cardId = $this->chatSettings->getCharacterCardId($this->userId);
        $characterCard = new CharacterCard($cardId);

        preg_match("/^(hp|mp|san)/i", $order, $attribute);
        $attribute = strtoupper($attribute[0]);
        $order = preg_replace("/^(hp|mp|san)[\s]*/i", "", $order, 1);
        preg_match("/^[+-]/", $order, $symbol);
        $symbol = $symbol[0];
        $expression = preg_replace("/^[+-][\s]*/", "", $order, 1);

        if (is_numeric($expression))
        {
            $value = (int) $expression;
            $result = $expression;

            if ($value < 0 || $value > Customization::getSetting("maxAttributeChange"))
            {
                $this->reply = Customization::getReply("changeAttributeValueOverstep");
                return;
            }
        }
        else
        {
            $dice = new Dice($expression);

            if ($dice->reason != "")
            {
                $this->reply = Customization::getReply("changeAttributeWrongExpression");
                return;
            }

            $value = $dice->rollResult;
            $result = $dice->getCompleteExpression();
        }

        $change = (int) ($symbol . $value);
        $response = $this->updateCard($cardId, $attribute, $change);
        $characterCard->set($attribute, $response->afterValue);
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
        if (!preg_match("/^(hp|mp|san)[\s]*[+-][\s]*/i", $order))
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
