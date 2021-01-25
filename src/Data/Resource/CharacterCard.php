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
    /** @var string[] Mapping between attribute name and the corresponding Chinese name. */
    protected const ATTRIBUTE_CN_NAMES = [
        "STR" => "力量", "CON" => "体质", "SIZ" => "体型",
        "DEX" => "敏捷",  "APP" => "外貌", "INT" => "智力",
        "IDEA" => "灵感", "POW" => "意志", "EDU" => "教育"
    ];

    /** @var string[] Mapping between states name and the corresponding Chinese name. */
    protected const STATE_CN_NAMES = [
        "HP" => "生命", "SAN" => "理智", "LUCK" => "幸运", "MP" => "魔法"
    ];

    /**
     * @inheritDoc
     *
     * @param array $data Character card data.
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->data["id"] ??= -1;
        $this->data["type"] ??= -1;
        $this->data["attributes"] ??= [];
        $this->data["states"] ??= [];
        // Lowercase all the skill names
        $this->data["skills"] = array_change_key_case($this->data["skills"] ?? []);
    }

    /**
     * Get attribute value.
     *
     * @param string $name Attribute name.
     *
     * @return int|false Attribute value.
     */
    public function getAttribute(string $name)
    {
        $name = strtoupper($name);
        $name = self::ATTRIBUTE_CN_NAMES[$name] ?? $name;

        if (!$this->has("attributes.{$name}")) {
            return false;
        }

        return $this->getInt("attributes.{$name}");
    }

    /**
     * Get state value.
     *
     * @param string $name State name.
     *
     * @return int|false State value.
     */
    public function getState(string $name)
    {
        $name = strtoupper($name);
        $name = self::STATE_CN_NAMES[$name] ?? $name;

        if (!$this->has("states.{$name}")) {
            return false;
        }

        return $this->getInt("states.{$name}");
    }

    /**
     * Get skill value.
     *
     * @param string $name Skill name.
     *
     * @return int|false Skill value.
     */
    public function getSkill(string $name)
    {
        $name = strtolower($name);

        if (!$this->has("skills.{$name}")) {
            return false;
        }

        return $this->getInt("skills.{$name}");
    }

    /**
     * Get item value.
     *
     * @param string $name Item name.
     *
     * @return int Item value.
     *
     * @throws ItemNotExistException Item does not exist.
     */
    public function getItem(string $name): int
    {
        if (false === ($value = $this->getAttribute($name)) &&
            false === ($value = $this->getState($name)) &&
            false === ($value = $this->getSkill($name))
        ) {
            throw new ItemNotExistException();
        }

        return $value;
    }

    /**
     * Set item value.
     *
     * @param string $name Item name.
     * @param int $value Item value.
     */
    public function setItem(string $name, int $value): void
    {
        $upperName = strtoupper($name);
        $lowerName = strtolower($name);

        if (array_key_exists($realName = self::ATTRIBUTE_CN_NAMES[$upperName] ?? $name, $this->data["attributes"])) {
            $this->data["attributes"][$realName] = $value;
        } elseif (array_key_exists($realName = self::STATE_CN_NAMES[$upperName] ?? $name, $this->data["states"])) {
            $this->data["states"][$realName] = $value;
        } elseif (array_key_exists($realName = $lowerName, $this->data["skills"])) {
            $this->data["skills"][$realName] = $value;
        }
    }
}
