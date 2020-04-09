<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Exception\InformativeException\APIException\UnexpectedErrorException;
use DiceRobot\Exception\InformativeException\IOException\FileDecodeException;
use DiceRobot\Exception\InformativeException\IOException\FileLostException;
use DiceRobot\Exception\InformativeException\IOException\FileUnwritableException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;
use DiceRobot\Exception\InformativeException\OrderErrorException;
use DiceRobot\Service\API\Response\GetCardResponse;
use DiceRobot\Service\Container\CharacterCard;
use DiceRobot\Service\Customization;

/**
 * Bind/Unbind COC character card.
 */
final class BindCard extends Action
{
    /**
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws FileUnwritableException
     * @throws InternalErrorException
     * @throws JSONDecodeException
     * @throws NetworkErrorException
     * @throws OrderErrorException
     * @throws UnexpectedErrorException
     */
    public function __invoke(): void
    {
        $order = preg_replace("/^\.card[\s]*/i", "", $this->message);

        // Unbind character card
        if ($order == "")
        {
            $this->chatSettings->setCharacterCardId($this->userId, NULL);
            $this->reply = Customization::getReply("bindCardUnbind");
            return;
        }

        $this->checkOrder($order);
        $this->sendPendingMessage();

        $cardId = (int) $order;
        $card = new CharacterCard($cardId, false);  // Create empty instance

        $card->import($this->getCard($cardId));  // Import character card
        $this->chatSettings->setCharacterCardId($this->userId, $cardId);

        $this->reply = Customization::getReply("bindCardSuccess");
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
        if (!preg_match("/^[1-9][0-9]*$/", $order))
            throw new OrderErrorException;
    }

    /**
     * Send message of pending request.
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    private function sendPendingMessage(): void
    {
        $message = Customization::getReply("bindCardPending");

        if ($this->chatType == "group")
            $this->coolq->sendGroupMessageAsync($this->chatId, $message);
        elseif ($this->chatType == "discuss")
            $this->coolq->sendDiscussMessageAsync($this->chatId, $message);
        elseif ($this->chatType == "private")
            $this->coolq->sendPrivateMessageAsync($this->chatId, $message);
    }

    /**
     * Get character card content.
     *
     * @param int $cardId Character card ID
     *
     * @return GetCardResponse The response
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     * @throws JSONDecodeException
     */
    private function getCard(int $cardId): GetCardResponse
    {
        $this->apiService->auth($this->selfId, $this->userId);

        return $this->apiService->getCard($cardId);
    }
}
