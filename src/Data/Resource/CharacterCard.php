<?php

declare(strict_types=1);

namespace DiceRobot\Data\Resource;

use DiceRobot\Data\Resource;
use DiceRobot\Exception\CharacterCardException\ItemNotExistException;

/**
 * Class CharacterCard
 *
 * Resource container. COC character card.
 *
 * @package DiceRobot\Data\Resource
 */
class CharacterCard extends Resource
{
    protected const ATTRIBUTE_ENG_NAMES = [
        "STR" => "力量", "CON" => "体质", "SIZ" => "体型",
        "DEX" => "敏捷",  "APP" => "外貌", "INT" => "智力",
        "IDEA" => "灵感", "POW" => "意志", "EDU" => "教育",
        "HP" => "生命", "SAN" => "理智", "LUCK" => "幸运", "MP" => "魔法"
    ];

    /**
     * @inheritDoc
     *
     * @param array $data Character card data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->data["id"] ??= -1;
        $this->data["type"] ??= 1;
        $this->data["attributes"] ??= [];
        // Lowercase all the skill names
        $this->data["skills"] = array_change_key_case($this->data["skills"] ?? []);
    }

    /**
     * Get attribute value.
     *
     * @param string $name Attribute name
     *
     * @return int Attribute value
     *
     * @throws ItemNotExistException
     */
    public function getAttribute(string $name): int
    {
        $name = strtoupper($name);
        $name = self::ATTRIBUTE_ENG_NAMES[$name] ?? $name;

        if (!$this->has("attributes.{$name}")) {
            throw new ItemNotExistException();
        }

        return $this->getInt("attributes.{$name}");
    }

    /**
     * Get skill value.
     *
     * @param string $name Skill name
     *
     * @return int Skill value or null
     *
     * @throws ItemNotExistException
     */
    public function getSkill(string $name): ?int
    {
        $name = strtolower($name);

        if (!$this->has("skills.{$name}")) {
            throw new ItemNotExistException();
        }

        return $this->getInt("skills.{$name}");
    }

    /**
     * Set attribute value.
     *
     * @param string $name Attribute name
     * @param int $value Attribute value
     */
    public function setAttribute(string $name, int $value): void
    {
        $name = strtoupper($name);
        $this->data["attributes"][self::ATTRIBUTE_ENG_NAMES[$name] ?? $name] = $value;
    }

    /**
     * Set skill value.
     *
     * @param string $name Skill name
     * @param int $value Skill value
     */
    public function setSkill(string $name, int $value): void
    {
        $name = strtolower($name);
        $this->data["skills"][$name] = $value;
    }
}
