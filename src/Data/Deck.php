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
    /** @var string[] Cards */
    protected array $cards = [];

    /** @var int[] Card counts */
    protected array $counts = [];

    /** @var int[] Original card counts */
    protected array $originalCounts = [];

    /**
     * The constructor.
     *
     * @param array $data Deck data
     */
    public function __construct(array $data)
    {
        $this->parseDeck($data);
    }

    /**
     * Parse deck.
     *
     * @param string[] $cards Cards
     */
    protected function parseDeck(array $cards): void
    {
        foreach ($cards as $card) {
            if (preg_match("/^::([1-9][0-9]*)::\s*(.*)$/", $card, $matches)) {
                $this->originalCounts[] = (int) $matches[1];
                $this->cards[] = $matches[2];
            } else {
                $this->originalCounts[] = 1;
                $this->cards[] = $card;
            }
        }

        $this->counts = $this->originalCounts;
    }

    /**
     * Get remanent cards number.
     *
     * @return int Remanent cards number
     */
    public function getCount(): int
    {
        return array_sum($this->counts);
    }

    /**
     * Get cards of the deck.
     *
     * @return string[] Cards
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
     * @return string Card content
     *
     * @throws RuntimeException
     */
    public function draw(): string
    {
        // Auto reset
        if ($this->getCount() <= 0) {
            $this->reset();
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
