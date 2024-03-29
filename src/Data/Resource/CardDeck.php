<?php

declare(strict_types=1);

namespace DiceRobot\Data\Resource;

use DiceRobot\Data\{Deck, Resource};
use DiceRobot\Exception\CardDeckException\{InvalidException, NotFoundException};
use DiceRobot\Exception\RuntimeException;

/**
 * Class Deck
 *
 * Resource container. TRPG card deck.
 *
 * @package DiceRobot\Data\Resource
 */
class CardDeck extends Resource
{
    /** @var CardDeck[] $publicDecks Mapping between public deck and the corresponding card deck. */
    protected static array $publicDecks = [];

    /**
     * Set card deck.
     *
     * @param array $deckNames Card deck names.
     * @param CardDeck $deck Card deck.
     */
    protected static function setDeck(array $deckNames, self $deck): void
    {
        $publicDecks = self::$publicDecks;

        foreach ($deckNames as $deckName) {
            $publicDecks[$deckName] = $deck;
        }

        self::$publicDecks = $publicDecks;
    }

    /**
     * Get card deck.
     *
     * @param string $publicDeckName Public card deck name.
     *
     * @return CardDeck|null Card deck.
     */
    public static function getCardDeck(string $publicDeckName): ?self
    {
        return self::$publicDecks[$publicDeckName] ?? null;
    }

    /**
     * @inheritDoc
     *
     * @param array $data Card deck data.
     */
    public function __construct(array $data)
    {
        list($decks, $publicDecks) = $this->parseDecks($data);

        parent::__construct($decks);

        self::setDeck($publicDecks, $this);
    }

    /**
     * Clone card deck.
     */
    public function __clone()
    {
        /** @var Deck $deck */
        foreach ($this->data as &$deck) {
            $deck = clone $deck;
        }
    }

    /**
     * Parse card decks.
     *
     * @param array $data Card deck data.
     *
     * @return array Parsed data.
     */
    protected function parseDecks(array $data): array
    {
        $decks = $publicDecks = [];

        foreach ($data as $name => $deck) {
            if ($name[0] != "_") {
                $publicDecks[] = $name;
            }

            $decks[$name] = new Deck($deck);
        }

        return [$decks, $publicDecks];
    }

    /**
     * Get specific deck of the card deck.
     *
     * @param string $deckName Deck name.
     *
     * @return Deck Deck.
     *
     * @throws InvalidException Deck cannot be found (Card deck incomplete).
     */
    public function getDeck(string $deckName): Deck
    {
        $deck = $this->get($deckName);

        if (!($deck instanceof Deck)) {
            throw new InvalidException();
        }

        return $deck;
    }

    /**
     * Reset the card deck.
     */
    public function reset(): void
    {
        /** @var Deck $deck */
        foreach ($this->data as $deck) {
            $deck->reset();
        }
    }

    /**
     * Draw a card from the card deck.
     *
     * @param string $publicDeckName Public deck name.
     *
     * @return string|false Success.
     *
     * @throws InvalidException
     * @throws NotFoundException Card deck cannot be found.
     */
    public function draw(string $publicDeckName)
    {
        if (!isset(self::$publicDecks[$publicDeckName])) {
            throw new NotFoundException();
        }

        try {
            $result = $this->drawCard($this->getDeck($publicDeckName), true);
        } catch (RuntimeException $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Draw card from the deck.
     *
     * @param Deck $deck Deck.
     * @param bool $isFirst Whether the deck is the first (entry).
     *
     * @return string Card content.
     *
     * @throws InvalidException
     * @throws RuntimeException Deck empty.
     */
    protected function drawCard(Deck $deck, bool $isFirst = false): string
    {
        // If the deck is the first (entry), check count
        if ($isFirst && $deck->getCount() <= 0) {
            throw new RuntimeException("Deck empty.");
        }

        $content = $deck->draw();

        return (string) preg_replace_callback("/{[&%]?(.+?)}/", function (array $matches) {
            return $this->drawCard($this->getDeck((string) $matches[1]));
        }, $content);
    }
}
