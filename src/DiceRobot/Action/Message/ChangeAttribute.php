<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action\Action;
use DiceRobot\Exception\ArithmeticExpressionErrorException;
use DiceRobot\Exception\CredentialException;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\PermissionDeniedException;
use DiceRobot\Exception\InformativeException\CharacterCardException\NotBoundException;
use DiceRobot\Exception\InformativeException\DiceException\DiceNumberOverstepException;
use DiceRobot\Exception\InformativeException\DiceException\SurfaceNumberOverstepException;
use DiceRobot\Exception\InformativeException\FileLostException;
use DiceRobot\Exception\InformativeException\FileUnwritableException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;
use DiceRobot\Exception\InformativeException\OrderErrorException;
use DiceRobot\Service\APIService;
use DiceRobot\Service\Container\CharacterCard;
use DiceRobot\Service\Container\Dice\Dice;
use DiceRobot\Service\Customization;

/**
 * Change investigator's attributes (HP, MP and SAN).
 */
final class ChangeAttribute extends Action
{
    /**
     * @throws InternalErrorException
     * @throws ArithmeticExpressionErrorException
     * @throws CredentialException
     * @throws DiceNumberOverstepException
     * @throws FileLostException
     * @throws FileUnwritableException
     * @throws JSONDecodeException
     * @throws NotBoundException
     * @throws OrderErrorException
     * @throws PermissionDeniedException
     * @throws SurfaceNumberOverstepException
     */
    public function __invoke(): void
    {
        $order = preg_replace("/^\./", "", $this->message, 1);
        $this->checkOrder($order);
        $cardId = $this->chatSettings->getCharacterCardId($this->userId);
        $characterCard = new CharacterCard($cardId);
        $characterCard->load();

        preg_match("/^(hp|mp|san)/i", $order, $attributeName);
        $attributeName = strtoupper($attributeName[0]);
        $order = preg_replace("/^(hp|mp|san)[\s]*/i", "", $order, 1);
        preg_match("/^[+-]/", $order, $addition);
        $addition = $addition[0] == "+";
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

        $updateResult = $this->updateCard($cardId, $attributeName, $addition, $value);
        $afterValue = $updateResult["after_value"];
        $characterCard->set($attributeName, $afterValue);
        $this->reply = Customization::getReply("changeAttributeResult", $this->userNickname,
            $attributeName, Customization::getWording("_attributeChangeWording", $addition),
            $result, $attributeName, $afterValue);
    }

    /**
     * Check the validity of the order.
     *
     * @param string $order Order
     *
     * @throws OrderErrorException
     */
    private function checkOrder(string $order): void
    {
        if (!preg_match("/^(hp|mp|san)[\s]*[+-][\s]*/i", $order))
            throw new OrderErrorException;
    }

    /**
     * Request API credential.
     *
     * @return string Credential
     *
     * @throws CredentialException
     */
    private function getCredential(): string
    {
        $response = APIService::getAPICredential($this->selfId);

        if ($response["code"] != 0)
        {
            $errMessage = "DiceRobot attribute change failed:\n" . "Attribute change user QQ ID: " . $this->userId;

            throw new CredentialException($errMessage);
        }

        return $response["data"]["credential"];
    }

    /**
     * Update character card.
     *
     * @param int $cardId Character card ID
     * @param string $attributeName Name of the attribute to be changed
     * @param bool $addition Addition/subtraction flag
     * @param int $value Addition/subtraction value
     *
     * @return array Response content
     *
     * @throws InternalErrorException
     * @throws CredentialException
     * @throws PermissionDeniedException
     */
    private function updateCard(int $cardId, string $attributeName, bool $addition, int $value): array
    {
        $response = APIService::updateCharacterCard($this->userId, $cardId, $attributeName, $addition, $value,
            $this->getCredential());

        if ($response["code"] == -3)
            throw new PermissionDeniedException();
        elseif ($response["code"] != 0)
            throw new InternalErrorException();

        return $response["data"];
    }
}
