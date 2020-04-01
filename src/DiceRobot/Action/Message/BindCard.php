<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action\Action;
use DiceRobot\Exception\CredentialException;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NotFoundException;
use DiceRobot\Exception\InformativeException\FileUnwritableException;
use DiceRobot\Exception\InformativeException\OrderErrorException;
use DiceRobot\Service\APIService;
use DiceRobot\Service\Container\CharacterCard;
use DiceRobot\Service\Customization;

/**
 * Bind/Unbind COC character card.
 */
final class BindCard extends Action
{
    /**
     * @throws InternalErrorException
     * @throws CredentialException
     * @throws FileUnwritableException
     * @throws NotFoundException
     * @throws OrderErrorException
     */
    public function __invoke(): void
    {
        $order = preg_replace("/^\.card[\s]*/i", "", $this->message, 1);

        if ($order == "")
        {
            $this->chatSettings->setCharacterCardId($this->userId, NULL);
            $this->reply = Customization::getReply("bindCardUnbind");
            return;
        }

        $this->checkOrder($order);
        $this->sendPendingMessage();

        $cardId = (int) $order;
        $cardContent = $this->getCardContent($cardId, $this->getCredential());
        $card = new CharacterCard($cardId);

        if (!$card->parse($cardContent["attributes"], $cardContent["status"], $cardContent["skills"]))
        {
            $this->reply = Customization::getReply("bindCardFormatError");
            return;
        }

        $card->save();
        $this->chatSettings->setCharacterCardId($this->userId, $cardId);
        $this->reply = Customization::getReply("bindCardSuccess");
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
        if (!preg_match("/^[1-9][0-9]*$/", $order))
            throw new OrderErrorException;
    }

    /**
     * Send message that request is pending.
     */
    private function sendPendingMessage(): void
    {
        $message = Customization::getReply("bindCardPending");

        if ($this->chatType == "group")
            APIService::sendGroupMessageAsync($this->chatId, $message);
        elseif ($this->chatType == "discuss")
            APIService::sendDiscussMessageAsync($this->chatId, $message);
        elseif ($this->chatType == "private")
            APIService::sendPrivateMessageAsync($this->chatId, $message);
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
            $errMessage = "DiceRobot bind character card failed: " . $response["message"] . "\n" .
                "Delinquent group ID: " . $this->groupId;

            throw new CredentialException($errMessage);
        }

        return $response["data"]["credential"];
    }

    /**
     * Get character card content.
     *
     * @param int $cardId Character card ID
     * @param string $credential API credential
     *
     * @return array Character card content
     *
     * @throws InternalErrorException
     * @throws NotFoundException
     */
    private function getCardContent(int $cardId, string $credential): array
    {
        $response = APIService::getCharacterCard($this->userId, $cardId, $credential);

        if ($response["code"] == -3)
            throw new NotFoundException();
        elseif ($response["code"] != 0)
            throw new InternalErrorException();

        return $response["data"];
    }
}
