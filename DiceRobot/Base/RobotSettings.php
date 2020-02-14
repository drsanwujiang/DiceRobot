<?php
namespace DiceRobot\Base;

/**
 * Class RobotSettings
 *
 * Utils class about robot settings, whose member methods are used to load/save configuration and get/set specific
 * setting of chat.
 */
final class RobotSettings
{
    private static string $currentConfigDir;
    private static string $configFilePath;
    private static ?array $robotSettings = NULL;

    public static function setConfigFilePath(string $chatType, string $chatId): void
    {
        self::$currentConfigDir = CONFIG_DIR_PATH . $chatType . "/";
        self::$configFilePath = CONFIG_DIR_PATH . $chatType . "/" . $chatId . ".json";
    }

    public static function loadSettings(): void
    {
        if (!file_exists(self::$currentConfigDir)) mkdir(self::$currentConfigDir, 0755, true);

        if (file_exists(self::$configFilePath))
        {
            $jsonString = file_get_contents(self::$configFilePath);
            $robotSettings = json_decode($jsonString, true);
            self::$robotSettings = is_null($robotSettings) ? array() : $robotSettings;
        }
        else self::$robotSettings = array();
    }

    private static function saveSettings(): void
    {
        ksort(self::$robotSettings);
        $jsonString = json_encode(self::$robotSettings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        file_put_contents(self::$configFilePath, $jsonString);
    }

    public static function getSetting(string $settingKey)
    {
        return self::$robotSettings[$settingKey] ?? NULL;
    }

    public static function setSetting(string $settingKey, $settingValue): void
    {
        if (is_null($settingValue)) unset(self::$robotSettings[$settingKey]);
        else self::$robotSettings[$settingKey] = $settingValue;

        self::saveSettings();
    }

    public static function getNickname(string $userId): ?string
    {
        return self::$robotSettings["nicknames"][$userId] ?? NULL;
    }

    public static function setNickname(string $userId, ?string $nickname): void
    {
        if (is_null($nickname)) unset(self::$robotSettings["nicknames"][$userId]);
        else self::$robotSettings["nicknames"][$userId] = $nickname;

        self::saveSettings();
    }
}
