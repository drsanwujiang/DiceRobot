<?php

declare(strict_types=1);

namespace DiceRobot\Data\Resource;

use DiceRobot\Data\Resource;
use DiceRobot\Exception\CharacterCardException\NotBoundException;

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
        parent::__construct($data);

        $this->data["active"] ??= true;
        $this->data["cocCheckRule"] ??= 0;
        $this->data["defaultSurfaceNumber"] ??= NULL;
        $this->data["robotNickname"] ??= NULL;

        $this->data["characterCards"] ??= [];
        $this->data["nicknames"] ??= [];
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
    public function setNickname(int $userId, string $nickname = NULL): void
    {
        if ($nickname)
            $this->data["nicknames"][$userId] = $nickname;
        else
            unset($this->data["nicknames"][$userId]);
    }

    /**
     * Set user's character card ID.
     *
     * @param int $userId User ID
     * @param int|null $cardId Character card ID
     */
    public function setCharacterCardId(int $userId, int $cardId = NULL): void
    {
        if ($cardId)
            $this->data["characterCards"][$userId] = $cardId;
        else
            unset($this->data["characterCards"][$userId]);
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
        if (!$this->has("nicknames.{$userId}"))
            return NULL;

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
        if (!$this->has("characterCards.{$userId}"))
            throw new NotBoundException();

        return $this->getInt("characterCards.{$userId}");
    }
}
