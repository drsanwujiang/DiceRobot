<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\Deck;

use DiceRobot\Action\Message\DeckAction;
use DiceRobot\Exception\CardDeckException\NotFoundException;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class Set
 *
 * Set the default deck.
 *
 * @order deck set
 *
 *      Sample: .deck set FGO
 *
 * @package DiceRobot\Action\Message\Deck
 */
class Set extends DeckAction
{
    /**
     * @inheritDoc
     *
     * @throws OrderErrorException|NotFoundException
     */
    public function __invoke(): void
    {
        list($deckName) = $this->parseOrder();

        if (!$this->checkPermission()) {
            return;
        }

        $cardDeck = $this->resource->getCardDeck($deckName);

        $this->chatSettings->set("defaultCardDeck", $deckName);
        $this->chatSettings->set("cardDeck", $cardDeck);

        $this->setReply("deckSet", [
            "牌堆名称" => $deckName
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
        if (!preg_match("/^(\S+)$/", $this->order, $matches)) {
            throw new OrderErrorException;
        }

        $deckName = (string) $matches[1];

        /**
         * @var string $deckName Card deck name.
         */
        return [$deckName];
    }
}
