<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\Deck;

use DiceRobot\Action\Message\DeckAction;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class Clear
 *
 * Clear default deck.
 *
 * @order deck clear
 *
 *      Sample: .deck clear
 *
 * @package DiceRobot\Action\Message\Deck
 */
class Clear extends DeckAction
{
    /**
     * @inheritDoc
     *
     * @throws OrderErrorException
     */
    public function __invoke(): void
    {
        $this->parseOrder();

        if (!$this->checkPermission() || !$this->checkDeck()) {
            return;
        }

        $this->chatSettings->set("defaultCardDeck", null);
        $this->chatSettings->set("cardDeck", null);

        $this->setReply("deckClear");
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
