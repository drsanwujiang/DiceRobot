<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Resource\CardDeck;
use DiceRobot\Exception\CardDeckException\{InvalidException, NotFoundException};
use DiceRobot\Exception\OrderErrorException;

/**
 * Class Draw
 *
 * Draw a card from a card deck.
 *
 * @order draw
 *
 *      Sample: .draw
 *              .draw FGO
 *              .draw 10
 *              .draw FGO 10
 *
 * @package DiceRobot\Action\Message
 */
class Draw extends MessageAction
{
    /**
     * @inheritDoc
     *
     * @throws InvalidException|NotFoundException|OrderErrorException
     */
    public function __invoke(): void
    {
        list($deckName, $count) = $this->parseOrder();

        if (!$this->checkRange($count)) {
            return;
        }

        if (is_null($deckName)) {
            $deckName = $this->chatSettings->get("defaultCardDeck");
            $deck = $this->chatSettings->get("cardDeck");

            if (!is_string($deckName) || !($deck instanceof CardDeck)) {
                $this->setReply("drawDeckNotSet");

                return;
            }
        } else {
            $deck = $this->resource->getCardDeck($deckName);
        }

        list($empty, $result) = $this->draw($deck, $deckName, $count);

        $this->setReply("drawResult", [
            "昵称" => $this->getNickname(),
            "抽牌结果" => $result
        ]);

        // If the deck is empty, reset the card deck
        if ($deck->getDeck($deckName)->getCount() <= 0) {
            $deck->reset();
        }

        // If the deck is run out of, send message
        if ($empty) {
            $this->setReply("drawDeckEmpty");
        }
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
        $deckName = null;
        $count = 1;

        if (preg_match("/^([1-9][0-9]*)?$/", $this->order, $matches)) {
            $count = (int) ($matches[1] ?? 1);
        } elseif (preg_match("/^(\S+?)\s+([1-9][0-9]*)$/", $this->order, $matches)) {
            $deckName = $matches[1];
            $count = (int) $matches[2];
        } elseif (preg_match("/^(\S+?)$/", $this->order, $matches)) {
            $deckName = $matches[1];
        } else {
            throw new OrderErrorException;
        }

        /**
         * @var null $deckName Deck name.
         * @var int $count Count of drawing card.
         */
        return [$deckName, $count];
    }

    /**
     * Check the range.
     *
     * @param int $count Count of drawing card.
     *
     * @return bool Validity.
     */
    protected function checkRange(int $count): bool
    {
        $maxDrawCount = $this->config->getOrder("maxDrawCount");

        if ($count > $maxDrawCount) {
            $this->setReply("drawCountOverstep", [
                "最大抽牌次数" => $maxDrawCount
            ]);

            return false;
        }

        return true;
    }

    /**
     * Draw card(s) from the card deck.
     *
     * @param CardDeck $deck Card deck to draw from.
     * @param string $deckName Deck name.
     * @param int $count Count of drawing card.
     *
     * @return array Empty flag and draw result.
     *
     * @throws InvalidException|NotFoundException
     */
    protected function draw(CardDeck $deck, string $deckName, int $count): array
    {
        $empty = false;
        $result = "";

        while ($count--) {
            if (false === $content = $deck->draw($deckName)) {
                // Deck is empty, stop drawing
                $empty = true;

                break;
            } else {
                $result .= $content . "\n";
            }
        }

        return [$empty, rtrim($result)];
    }
}
