<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action\Action;
use DiceRobot\Exception\ArithmeticExpressionErrorException;
use DiceRobot\Exception\CredentialException;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\PermissionDeniedException;
use DiceRobot\Exception\InformativeException\CharacterCardException\ItemNotExistException;
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
 * Sanity check.
 */
final class SanCheck extends Action
{
    /**
     * @throws ArithmeticExpressionErrorException
     * @throws CredentialException
     * @throws DiceNumberOverstepException
     * @throws FileLostException
     * @throws FileUnwritableException
     * @throws InternalErrorException
     * @throws ItemNotExistException
     * @throws JSONDecodeException
     * @throws NotBoundException
     * @throws OrderErrorException
     * @throws PermissionDeniedException
     * @throws SurfaceNumberOverstepException
     */
    public function __invoke(): void
    {
        $order = preg_replace("/^\.sc[\s]*/i", "", $this->message, 1);
        $this->checkOrder($order);

        $cardId = $this->chatSettings->getCharacterCardId($this->userId);
        $characterCard = new CharacterCard($cardId);
        $characterCard->load();

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
        $updateResult = $this->updateCard($cardId, $checkResult, $decreases);

        $checkSuccess = $updateResult["check_success"];
        $maxSanity = 99 - $characterCard->get("克苏鲁神话") ?? 0;
        $checkResultString = $dices[2]->getCompleteExpression();
        $decreaseResult = $checkSuccess ? $decreaseResults[0] : $decreaseResults[1];

        $characterCard->set("SAN", $updateResult["after_sanity"]);
        $this->reply = Customization::getReply("sanCheckResult", $this->userNickname, $checkResultString,
            $updateResult["before_sanity"], Customization::getWording("_sanCheckLevel", $checkSuccess),
            $decreaseResult, $updateResult["after_sanity"], $maxSanity);
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
        if (!preg_match("/^[\S]+\/[\S]+$/i", $order))
            throw new OrderErrorException;
    }

    /**
     * Check the value range.
     *
     * @param int $value Value
     *
     * @return bool Flag of validity
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
     * Request APIService credential.
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
            $errMessage = "DiceRobot sanity check failed:\n" . "Sanity check user QQ ID: " . $this->userId;

            throw new CredentialException($errMessage);
        }

        return $response["data"]["credential"];
    }

    /**
     * Update character card.
     *
     * @param int $cardId Character card ID
     * @param int $checkResult Check result
     * @param array $decreases Decreases
     *
     * @return array Response content
     *
     * @throws InternalErrorException
     * @throws CredentialException
     * @throws PermissionDeniedException
     */
    private function updateCard(int $cardId, int $checkResult, array $decreases): array
    {
        $response = APIService::sanityCheck($this->userId, $cardId, $checkResult, $decreases, $this->getCredential());

        if ($response["code"] == -3)
            throw new PermissionDeniedException();
        elseif ($response["code"] != 0)
            throw new InternalErrorException();

        return $response["data"];
    }
}
