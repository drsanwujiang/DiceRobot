<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\Deck;

use DiceRobot\Action\Message\DeckAction;
use DiceRobot\Data\Resource\CardDeck;
use DiceRobot\Exception\CardDeckException\InvalidException;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class Show
 *
 * Show the rest of the default deck.
 *
 * @order deck show
 *
 *      Sample: .deck show
 *
 * @package DiceRobot\Action\Message\Deck
 */
class Show extends DeckAction
{
    /**
     * @inheritDoc
     *
     * @throws OrderErrorException|InvalidException
     */
    public function __invoke(): void
    {
        $this->parseOrder();

        if (!$this->checkDeck()) {
            return;
        }

        $defaultCardDeck = $this->chatSettings->getString("defaultCardDeck");
        /** @var CardDeck $cardDeck */
        $cardDeck = $this->chatSettings->get("cardDeck");
        $deck = $cardDeck->getDeck($defaultCardDeck);

        $this->setReply("deckShow", [
            "卡牌列表" => join(" | ", array_unique($deck->getCards()))
        ]);
    }

    /**
     * @inheritDoc
     *
     * @return array Parsed elements.
     *
     * @throws OrderErrorException Order is invalid.
     */
    protected function parseOrder(): array
    {
        if (!preg_match("/^$/", $this->order)) {
            throw new OrderErrorException;
        }

        return [];
    }
}
