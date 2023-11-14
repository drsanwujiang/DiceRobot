<?php

declare(strict_types=1);

namespace DiceRobot\Data;

use DiceRobot\Exception\RuntimeException;
use DiceRobot\Util\Random;

/**
 * Class Deck
 *
 * Deck.
 *
 * @package DiceRobot\Data
 */
class Deck
{
    /** @var string[] Top of the deck. */
    protected array $deckTop = [];

    /** @var string[] Bottom of the deck. */
    protected array $deckBottom = [];

    /** @var string[] Remaining cards. */
    protected array $cards = [];

    /** @var int[] Remaining card counts. */
    protected array $counts = [];

    /** @var int[] Original card counts. */
    protected array $originalCounts = [];

    /** @var int Total card counts. */
    protected int $sum;

    /**
     * The constructor.
     *
     * @param array $data Deck data.
     */
    public function __construct(array $data)
    {
        $this->parseDeck($data);
    }

    /**
     * Parse deck.
     *
     * @param string[] $cards Cards.
     */
    protected function parseDeck(array $cards): void
    {
        foreach ($cards as $card) {
            if (preg_match("/^::([1-9]\d*)::\s*(.*)$/", $card, $matches)) {
                $this->originalCounts[] = (int) $matches[1];
                $this->cards[] = $matches[2];
            } else {
                $this->originalCounts[] = 1;
                $this->cards[] = $card;
            }
        }

        $this->counts = $this->originalCounts;
        $this->sum = array_sum($this->originalCounts);
    }

    /**
     * Put a card back on the top of the deck.
     *
     * @param string $card The card.
     */
    public function putBackOnTop(string $card): void
    {
        array_unshift($this->deckTop, $card);
    }

    /**
     * Put a card back on the bottom of the deck.
     *
     * @param string $card The card.
     */
    public function putBackOnBottom(string $card): void
    {
        array_push($this->deckBottom, $card);
    }

    /**
     * Get total cards number.
     *
     * @return int Total cards number.
     */
    public function getSum(): int
    {
        $sum = $this->sum ?? array_sum($this->originalCounts);  // For compatibility

        return $sum + count($this->deckTop) + count($this->deckBottom);
    }

    /**
     * Get remaining cards number.
     *
     * @return int Remaining cards number.
     */
    public function getCount(): int
    {
        return array_sum($this->counts) + count($this->deckTop) + count($this->deckBottom);
    }

    /**
     * Get cards of the deck.
     *
     * @return string[] Cards.
     */
    public function getCards(): array
    {
        return $this->cards;
    }

    /**
     * Reset the deck.
     */
    public function reset(): void
    {
        $this->counts = $this->originalCounts;
    }

    /**
     * Draw a card from this deck.
     *
     * @return string Card content.
     *
     * @throws RuntimeException Drawing error, but this error should not be thrown, for the statement should be
     *                          unreachable.
     */
    public function draw(): string
    {
        // Auto reset
        if ($this->getCount() <= 0) {
            $this->reset();
        }

        // Check top of the deck
        if (!empty($this->deckTop)) {
            return (string) array_shift($this->deckTop);
        }

        // Check bottom of the deck
        if (array_sum($this->counts) <= 0 && !empty($this->deckBottom)) {
            return (string) array_shift($this->deckBottom);
        }

        $rand = Random::generate(1, $this->getCount())[0];

        for ($i = 0; $i < count($this->cards); $i++) {
            if ($rand <= $this->counts[$i]) {
                $this->counts[$i]--;  // Draw without replacement

                return $this->cards[$i];
            }

            $rand -= $this->counts[$i];
        }

        // This statement should be unreachable
        throw new RuntimeException("Drawing error.");
    }
}
