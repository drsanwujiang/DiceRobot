<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Resource\CharacterCard;
use DiceRobot\Exception\OrderErrorException;
use DiceRobot\Exception\ApiException\{InternalErrorException, NetworkErrorException, UnexpectedErrorException};

/**
 * Class Card
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
class Card extends MessageAction
{
    /**
     * @inheritDoc
     *
     * @throws InternalErrorException|NetworkErrorException|OrderErrorException|UnexpectedErrorException
     */
    public function __invoke(): void
    {
        list($cardId) = $this->parseOrder();

        if ($cardId) {
            // Bind character card
            $this->sendMessageAsync($this->config->getString("reply.cardPending"));

            // Import character card
            $card = new CharacterCard($this->getCard($cardId));
            $this->resource->setCharacterCard($cardId, $card);
            $this->chatSettings->setCharacterCardId($this->message->sender->id, $cardId);

            $this->reply = $this->config->getString("reply.cardBind");
        } else {
            //Unset character card ID
            $this->chatSettings->setCharacterCardId($this->message->sender->id);

            $this->reply = $this->config->getString("reply.cardUnbind");
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
        if (!preg_match("/^([1-9][0-9]*)?$/", $this->order, $matches)) {
            throw new OrderErrorException;
        }

        /**
         * @var int $cardId Character card ID
         */
        return [$cardId];
    }

    /**
     * Get character card content.
     *
     * @param int $cardId Character card ID
     *
     * @return array Card data
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    protected function getCard(int $cardId): array
    {
        return $this->api->getCard(
            $cardId,
            $this->api->auth($this->robot->getId(), $this->message->sender->id)->token
        )->data;
    }
}
