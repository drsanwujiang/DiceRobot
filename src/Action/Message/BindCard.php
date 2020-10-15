<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Resource\CharacterCard;
use DiceRobot\Data\Response\GetCardResponse;
use DiceRobot\Exception\OrderErrorException;
use DiceRobot\Exception\ApiException\{InternalErrorException, NetworkErrorException, UnexpectedErrorException};

/**
 * Class BindCard
 *
 * Bind/Unbind COC character card.
 *
 * @order card
 *
 *      Sample: .card
 *              .card 233
 *
 * @package DiceRobot\Action\Message
 */
class BindCard extends MessageAction
{
    /**
     * @inheritDoc
     *
     * @throws InternalErrorException|NetworkErrorException|OrderErrorException|UnexpectedErrorException
     */
    public function __invoke(): void
    {
        list($cardId) = $this->parseOrder();

        // Bind character card
        if ($cardId)
        {
            $this->sendMessage($this->config->getString("reply.bindCardPending"));

            // Import character card
            $card = new CharacterCard($this->getCard($cardId)->data);
            $this->resource->setCharacterCard($cardId, $card);
            $this->chatSettings->setCharacterCardId($this->message->sender->id, $cardId);

            $this->reply = $this->config->getString("reply.bindCardSuccess");
        }
        // Unbind character card
        else
        {
            //Unset character card ID
            $this->chatSettings->setCharacterCardId($this->message->sender->id);

            $this->reply = $this->config->getString("reply.bindCardUnbind");
        }
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
        if (!preg_match("/^([1-9][0-9]*)?$/", $this->order, $matches))
            throw new OrderErrorException;

        /** @var int $cardId */
        $cardId = (int) ($matches[1] ?? "");

        return [$cardId];
    }

    /**
     * Get character card content.
     *
     * @param int $cardId Character card ID
     *
     * @return GetCardResponse The response
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    protected function getCard(int $cardId): GetCardResponse
    {
        $response = $this->api->auth($this->robot->getId(), $this->message->sender->id);

        return $this->api->getCard($cardId, $response->token);
    }
}
