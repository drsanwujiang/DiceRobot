<?php
namespace DiceRobot\Service\Container;

use DiceRobot\Exception\InformativeException\CharacterCardException\ItemNotExistException;
use DiceRobot\Exception\InformativeException\IOException\FileDecodeException;
use DiceRobot\Exception\InformativeException\IOException\FileLostException;
use DiceRobot\Exception\InformativeException\IOException\FileUnwritableException;
use DiceRobot\Service\API\Response\GetCardResponse;
use DiceRobot\Service\IOService;

/**
 * COC character card.
 */
class CharacterCard
{
    const ATTRIBUTE_ENG_NAMES = [
        "STR" => "力量", "CON" => "体质", "SIZ" => "体型",
        "DEX" => "敏捷",  "APP" => "外貌", "INT" => "智力",
        "IDEA" => "灵感", "POW" => "意志", "EDU" => "教育",
        "HP" => "生命", "SAN" => "理智", "LUCK" => "幸运", "MP" => "魔法"
    ];

    private static string $cardDir;

    private int $id;
    private int $type;
    private array $attributes;
    private array $skills;

    /**
     * The constructor.
     *
     * @param int $id Card ID
     * @param bool $load Load card flag
     *
     * @throws FileDecodeException
     * @throws FileLostException
     */
    public function __construct(int $id, bool $load = true)
    {
        $this->id = $id;

        if ($load)
            $this->load();
    }

    /**
     * Set the dir of character cards.
     *
     * @param string $cardDir The dir
     */
    public static function setDir(string $cardDir): void
    {
        self::$cardDir = $cardDir;
    }

    /**
     * Import character card.
     *
     * @param GetCardResponse $response The response
     *
     * @throws FileUnwritableException
     */
    public function import(GetCardResponse $response): void
    {
        $this->type = $response->type;
        $this->attributes = $response->attributes;
        $this->skills = $response->skills;

        $this->save();
    }

    /**
     * Load character card.
     *
     * @throws FileDecodeException
     * @throws FileLostException
     */
    private function load(): void
    {
        $cardPath = self::$cardDir . $this->id . ".json";
        $card = IOService::getFile($cardPath);
        $this->type = $card["type"] ?? 1;
        $this->attributes = $card["attributes"];
        $this->skills = $card["skills"];
    }

    /**
     * Save character card.
     *
     * @throws FileUnwritableException
     */
    private function save(): void
    {
        $cardContent = [
            "id" => $this->id,
            "type" => $this->type,
            "attributes" => $this->attributes,
            "skills" => $this->skills
        ];

        if (!file_exists(self::$cardDir))
            mkdir(self::$cardDir, 0755, true);

        $cardPath = self::$cardDir . $this->id . ".json";
        IOService::putFile($cardPath, $cardContent);
    }

    /**
     * Get the value of attribute/skill.
     *
     * @param string $name Attribute/skill name
     *
     * @return int Attribute/skill value
     *
     * @throws ItemNotExistException
     */
    public function get(string $name): int
    {
        $name = self::ATTRIBUTE_ENG_NAMES[$name] ?? $name;
        $value = $this->attributes[$name] ?? $this->skills[$name] ?? NULL;

        if (is_null($value))
            throw new ItemNotExistException();

        return $value;
    }

    /**
     * Set the value of attribute/skill.
     *
     * @param $attributeName
     * @param $attribute
     *
     * @throws FileUnwritableException
     */
    public function set($attributeName, $attribute): void
    {
        $attributeName = self::ATTRIBUTE_ENG_NAMES[$attributeName] ?? $attributeName;
        $this->attributes[$attributeName] = $attribute;

        $this->save();
    }
}
