<?php

declare(strict_types=1);

namespace DiceRobot\Action\Message;

use DiceRobot\Action\MessageAction;
use DiceRobot\Data\Resource\CardDeck;
use DiceRobot\Exception\OrderErrorException;
use DiceRobot\Exception\CardDeckException\{InvalidException, NotFoundException};

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
        list($deckName, $drawCount) = $this->parseOrder();

        if (!$this->checkRange($drawCount)) {
            return;
        }

        if (is_null($deckName)) {
            $deckName = $this->chatSettings->get("defaultCardDeck");
            $deck = $this->chatSettings->get("cardDeck");

            if (!is_string($deckName) || !($deck instanceof CardDeck)) {
                $this->setReply("deckNotSet");

                return;
            }
        } else {
            $deck = $this->resource->getCardDeck($deckName);
        }

        list($empty, $result) = $this->draw($deck, $deckName, $drawCount);

        $this->setReply("drawResult", [
            "昵称" => $this->getNickname(),
            "抽牌结果" => $result
        ]);

        // If deck is empty, send message and reset the deck
        if ($empty) {
            $deck->reset();
        }

        $this->setReply("drawDeckEmpty");
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
        $deckName = null;
        $drawCount = 1;

        if (preg_match("/^([1-9][0-9]*)?$/", $this->order, $matches)) {
            $drawCount = (int) ($matches[1] ?? 1);
        } elseif (preg_match("/^(\S+?)\s+([1-9][0-9]*)$/", $this->order, $matches)) {
            $deckName = $matches[1];
            $drawCount = (int) $matches[2];
        } elseif (preg_match("/^(\S+?)$/", $this->order, $matches)) {
            $deckName = $matches[1];
        } else {
            throw new OrderErrorException;
        }

        /**
         * @var null $deckName Deck name
         * @var int $drawCount Count of drawing card
         */
        return [$deckName, $drawCount];
    }

    /**
     * @param int $drawCount Count of drawing card
     *
     * @return bool
     */
    protected function checkRange(int $drawCount): bool
    {
        $maxDrawCount = $this->config->getOrder("maxDrawCount");

        if ($drawCount > $maxDrawCount) {
            $this->setReply("drawCountOverstep", [
                "最大抽牌次数" => $maxDrawCount
            ]);

            return false;
        }

        return true;
    }

    /**
     * @param CardDeck $deck Card deck to draw from
     * @param string $deckName Deck name
     * @param int $drawCount Count of drawing card
     *
     * @return array Empty flag and draw result
     *
     * @throws InvalidException|NotFoundException
     */
    protected function draw(CardDeck $deck, string $deckName, int $drawCount): array
    {
        $empty = false;
        $result = "";

        while ($drawCount--) {
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
