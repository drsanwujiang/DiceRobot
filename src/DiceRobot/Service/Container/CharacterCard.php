<?php
namespace DiceRobot\Service\Container;

use DiceRobot\Exception\InformativeException\CharacterCardException\ItemNotExistException;
use DiceRobot\Exception\InformativeException\FileLostException;
use DiceRobot\Exception\InformativeException\FileUnwritableException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;
use DiceRobot\Service\IOService;

/**
 * COC character card container.
 */
final class CharacterCard
{
    private const ATTRIBUTE_ENG_NAMES = [
        "STR" => "力量", "CON" => "体质", "SIZ" => "体型",
        "DEX" => "敏捷",  "APP" => "外貌", "INT" => "智力",
        "IDEA" => "灵感", "POW" => "意志", "EDU" => "教育",
        "HP" => "生命", "SAN" => "理智", "LUCK" => "幸运", "MP" => "魔法"
    ];

    private static string $cardDir;

    private int $cardId;
    private bool $success = true;
    private array $attributes;
    private array $skills;

    public function __construct(int $cardId)
    {
        $this->cardId = $cardId;
    }

    public static function setDir(string $cardDir): void
    {
        self::$cardDir = $cardDir;
    }

    public function parse(array $attributesTable, array $statusTable, array $skillsTable): bool
    {
        $this->parseAttributes($attributesTable, $statusTable);
        $this->parseSkills($skillsTable);

        return $this->success;
    }

    private function parseAttributes(array &$attributesTable, array &$statusTable): void
    {
        if (!isset($attributesTable[0][0]) || $attributesTable[0][0] != "属性")
        {
            $this->success = false;
            return;
        }

        $this->attributes["力量"] = (int) ($attributesTable[2][1] ?? 0);
        $this->attributes["体质"] = (int) ($attributesTable[2][3] ?? 0);
        $this->attributes["体型"] = (int) ($attributesTable[2][5] ?? 0);
        $this->attributes["敏捷"] = (int) ($attributesTable[8][1] ?? 0);
        $this->attributes["外貌"] = (int) ($attributesTable[8][3] ?? 0);
        $this->attributes["智力"] = (int) ($attributesTable[8][5] ?? 0);  // INT and IDEA is same
        $this->attributes["灵感"] = (int) ($attributesTable[8][5] ?? 0);  // INT and IDEA is same
        $this->attributes["意志"] = (int) ($attributesTable[14][1] ?? 0);
        $this->attributes["教育"] = (int) ($attributesTable[14][3] ?? 0);
        $this->attributes["生命"] = (int) ($statusTable[4][0] ?? 0);
        $this->attributes["理智"] = (int) ($statusTable[13][0] ?? 0);
        $this->attributes["幸运"] = (int) ($statusTable[21][0] ?? 0);
        $this->attributes["魔法"] = (int) ($statusTable[29][0] ?? 0);
    }

    private function parseSkills(array &$skillsTable): void
    {
        if (!isset($skillsTable[0][0]) || isset($skillsTable[0][0]) != "技能表")
        {
            $this->success = false;
            return;
        }

        foreach ([1, 3, 15, 22, 24, 35] as $index)
            $skillsTable[$index] = array_pad($skillsTable[$index], 34, "");

        $skillNames = array_merge(
            array_slice($skillsTable[1], 2, 4),
            array_slice($skillsTable[3], 6, 3),
            array_slice($skillsTable[1], 9, 11),
            array_slice($skillsTable[3], 20, 6),
            array_slice($skillsTable[1], 26, 4),
            array_slice($skillsTable[3], 30, 4),
            array_slice($skillsTable[22], 2, 11),
            array_slice($skillsTable[24], 13, 1),
            array_slice($skillsTable[22], 14, 3),
            array_slice($skillsTable[24], 17, 3),
            array_slice($skillsTable[22], 20, 3),
            array_slice($skillsTable[24], 23, 2),
            array_slice($skillsTable[22], 25, 3),
            array_slice($skillsTable[24], 28, 2),
            array_slice($skillsTable[22], 30, 4)
        );
        $skillValues = array_merge(
            array_slice($skillsTable[15], 2, 32),
            array_slice($skillsTable[35], 2, 32)
        );
        $skillNames[9] = "计算机使用";
        $skillNames[16] = "电子学";

        for ($i = 0; $i < count($skillNames); $i++)
        {
            $skillName = trim($skillNames[$i]);

            if ($skillName != "")
                $this->skills[strtoupper($skillName)] = (int) $skillValues[$i];
        }
    }

    /**
     * Load attributes and skills of the character card.
     *
     * @throws FileLostException
     * @throws JSONDecodeException
     */
    public function load(): void
    {
        $cardPath = self::$cardDir . $this->cardId . ".json";
        $card = IOService::getFile($cardPath);
        $this->attributes = $card["attributes"];
        $this->skills = $card["skills"];
    }

    /**
     * Save character card.
     *
     * @throws FileUnwritableException
     */
    public function save(): void
    {
        $cardContent = ["cardId" => $this->cardId, "attributes" => $this->attributes, "skills" => $this->skills];

        if (!file_exists(self::$cardDir))
            mkdir(self::$cardDir, 0755, true);

        $cardPath = self::$cardDir . $this->cardId . ".json";
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
