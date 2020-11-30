<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message\Deck;

use DiceRobot\Action\Message\DeckAction;
use DiceRobot\Data\Resource\CardDeck;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class Reset
 *
 * Reset the default deck.
 *
 * @order deck reset
 *
 *      Sample: .deck reset
 *
 * @package DiceRobot\Action\Message\Deck
 */
class Reset extends DeckAction
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

        /** @var CardDeck $deck */
        $deck = $this->chatSettings->get("cardDeck");
        $deck->reset();

        $this->setReply("deckReset");
    }

    /**
     * @inheritDoc
     *
     * @return array Parsed elements.
     *
     * @throws OrderErrorException
     */
    protected function parseOrder(): array
    {
        if (!preg_match("/^$/", $this->order)) {
            throw new OrderErrorException;
        }

        return [];
    }
}
