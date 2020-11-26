<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Resource\CardDeck;
use DiceRobot\Exception\OrderErrorException;
use DiceRobot\Exception\CardDeckException\{InvalidException, NotFoundException};

/**
 * Class Deck
 *
 * Manage default card deck.
 *
 * @order deck
 *
 *      Sample: .deck set FGO
 *              .deck reset
 *              .deck show
 *              .deck unset
 *
 * @package DiceRobot\Action\Message
 */
class Deck extends MessageAction
{
    /**
     * @inheritDoc
     *
     * @throws InvalidException|NotFoundException|OrderErrorException
     */
    public function __invoke(): void
    {
        list($order, $subOrder) = $this->parseOrder();

        switch ($order) {
            case "set":
                $deck = $this->resource->getCardDeck($subOrder);

                $this->chatSettings->set("defaultCardDeck", $subOrder);
                $this->chatSettings->set("cardDeck", $deck);

                $this->setReply("deckSet", [
                    "牌堆名称" => $subOrder
                ]);

                break;
            case "reset":
                if (!$this->checkDeck()) {
                    return;
                }

                /** @var CardDeck $deck */
                $deck = $this->chatSettings->get("cardDeck");
                $deck->reset();

                $this->setReply("deckReset");

                break;
            case "show":
                if (!$this->checkDeck()) {
                    return;
                }

                $defaultCardDeck = $this->chatSettings->getString("defaultCardDeck");
                /** @var CardDeck $cardDeck */
                $cardDeck = $this->chatSettings->get("cardDeck");
                $deck = $cardDeck->getDeck($defaultCardDeck);

                $this->setReply("deckShowRest", [
                    "卡牌列表" => join(" | ", array_unique($deck->getCards()))
                ]);

                break;
            case "clear":
            case "unset":
                if (!$this->checkDeck()) {
                    return;
                }

                $this->chatSettings->set("defaultCardDeck", null);
                $this->chatSettings->set("cardDeck", null);

                $this->setReply("deckUnset");

                break;
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
        $subOrder = "";

        if (preg_match("/^(set)\s*(\S+)$/", $this->order, $matches)) {
            $order = $matches[1];
            $subOrder = $matches[2];
        } elseif (preg_match("/^(reset|show|unset|clear)$/", $this->order, $matches)) {
            $order = $matches[1];
        } else {
            throw new OrderErrorException;
        }

        /**
         * @var string $order Order
         * @var string $subOrder Sub-orders
         */
        return [$order, $subOrder];
    }

    /**
     * Whether the default card deck has been set.
     *
     * @return bool
     */
    protected function checkDeck(): bool
    {
        if (
            !is_string($this->chatSettings->get("defaultCardDeck")) ||
            !($this->chatSettings->get("cardDeck") instanceof CardDeck)
        ) {
            $this->setReply("deckNotSet");

            return false;
        }

        return true;
    }
}
