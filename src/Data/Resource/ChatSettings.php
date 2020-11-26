<?php

declare(strict_types=1);

namespace DiceRobot\Data\Resource;

use DiceRobot\Data\Resource;
use DiceRobot\Exception\CharacterCardException\NotBoundException;

use const DiceRobot\DEFAULT_CHAT_SETTINGS;

/**
 * Class ChatSettings
 *
 * Resource container. Chat settings.
 *
 * @package DiceRobot\Data\Resource
 */
class ChatSettings extends Resource
{
    /**
     * @inheritDoc
     *
     * @param array $data Chat settings data
     */
    public function __construct(array $data = [])
    {
        $data = array_replace_recursive(DEFAULT_CHAT_SETTINGS, $data);

        if (is_string($data["cardDeck"])) {
            $data["cardDeck"] = unserialize($data["cardDeck"]);
        }

        parent::__construct($data);
    }

    /**
     * Return JSON serialized data.
     *
     * @return string JSON serialized data
     */
    public function __toString(): string
    {
        $data = $this->data;

        if ($data["cardDeck"] instanceof CardDeck) {
            $data["cardDeck"] = serialize($this->data["cardDeck"]);
        }

        return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Set setting.
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     */
    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Set user nickname.
     *
     * @param int $userId User ID
     * @param string|null $nickname User nickname
     */
    public function setNickname(int $userId, string $nickname = null): void
    {
        if ($nickname) {
            $this->data["nicknames"][$userId] = $nickname;
        } else {
            unset($this->data["nicknames"][$userId]);
        }
    }

    /**
     * Set user's character card ID.
     *
     * @param int $userId User ID
     * @param int|null $cardId Character card ID
     */
    public function setCharacterCardId(int $userId, int $cardId = null): void
    {
        if ($cardId) {
            $this->data["characterCards"][$userId] = $cardId;
        } else {
            unset($this->data["characterCards"][$userId]);
        }
    }

    /**
     * Get user nickname.
     *
     * @param int $userId User ID
     *
     * @return string|null User nickname
     */
    public function getNickname(int $userId): ?string
    {
        if (!$this->has("nicknames.{$userId}")) {
            return null;
        }

        return $this->getString("nicknames.{$userId}");
    }

    /**
     * Get user's character card ID.
     *
     * @param int $userId User ID
     *
     * @return int Character card ID
     *
     * @throws NotBoundException
     */
    public function getCharacterCardId(int $userId): int
    {
        if (!$this->has("characterCards.{$userId}")) {
            throw new NotBoundException();
        }

        return $this->getInt("characterCards.{$userId}");
    }
}
