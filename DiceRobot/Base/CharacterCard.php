<?php
namespace DiceRobot\Base;

use DiceRobot\Exception\FileLostException;

/**
 * Class CharacterCard
 *
 * Container of COC character card.
 */
final class CharacterCard
{
    private static string $characterCardDir;
    private string $characterCardPath;

    private int $cardId;
    private bool $success = true;
    private array $attributes;
    private array $skills;

    public function __construct(int $cardId)
    {
        self::$characterCardDir = CHARACTER_CARD_DIR_PATH;
        $this->characterCardPath = CHARACTER_CARD_DIR_PATH . $cardId . ".json";
        $this->cardId = $cardId;
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

        $this->attributes["力量"] = intval($attributesTable[2][1]);
        $this->attributes["体质"] = intval($attributesTable[2][3]);
        $this->attributes["体型"] = intval($attributesTable[2][5]);
        $this->attributes["敏捷"] = intval($attributesTable[8][1]);
        $this->attributes["外貌"] = intval($attributesTable[8][3]);
        $this->attributes["智力"] = intval($attributesTable[8][5]);
        $this->attributes["灵感"] = intval($attributesTable[8][5]);
        $this->attributes["意志"] = intval($attributesTable[14][1]);
        $this->attributes["教育"] = intval($attributesTable[14][3]);
        $this->attributes["理智"] = intval($statusTable[13]);
        $this->attributes["幸运"] = intval($statusTable[21]);
    }

    private function parseSkills(array &$skillsTable): void
    {
        if (!isset($skillsTable[0][0]) || isset($skillsTable[0][0]) != "技能表")
        {
            $this->success = false;
            return;
        }

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

        for ($i = 0; $i <= 63; $i++)
        {
            $skillName = trim($skillNames[$i]);

            if ($skillName != "")
                $this->skills[$skillName] = intval($skillValues[$i]);
        }
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    public function load(): void
    {
        if (!file_exists($this->characterCardPath))
            throw new FileLostException();

        $jsonString = file_get_contents($this->characterCardPath);
        $characterCard = json_decode($jsonString, true);

        $this->attributes = $characterCard["attributes"];
        $this->skills = $characterCard["skills"];
    }

    public function save(): void
    {
        if (!file_exists(self::$characterCardDir))
            mkdir(self::$characterCardDir, 0755, true);

        $jsonString = json_encode(["cardId" => $this->cardId, "attributes" => $this->attributes,
            "skills" => $this->skills], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        file_put_contents($this->characterCardPath, $jsonString);
    }

    public function get($checkValueName): ?int
    {
        $attributeEngNames = ["STR" => "力量", "CON" => "体质", "SIZ" => "体型", "DEX" => "敏捷", "APP" => "外貌",
            "INT" => "智力", "IDEA" => "灵感", "POW" => "意志", "EDU" => "教育", "LUCK" => "幸运", "SAN" => "理智"];
        $attributeNames = ["力量", "体质", "体型", "敏捷", "外貌", "智力", "灵感", "意志", "教育", "幸运", "理智"];

        $checkValueName = $attributeEngNames[$checkValueName] ?? $checkValueName;

        if (in_array($checkValueName, $attributeNames))
            return $this->attributes[$checkValueName];
        else
            return $this->skills[$checkValueName] ?? NULL;
    }
}
