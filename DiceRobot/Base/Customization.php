<?php
namespace DiceRobot\Base;

use DiceRobot\Exception\FileLostException;
use DiceRobot\Exception\JSONDecodeException;
use Exception;

/**
 * Class Customization
 *
 * This class contains functions used to get customized data.
 */
final class Customization
{
    public static function getCustomSetting(string $settingKey)
    {
        return CUSTOM_SETTINGS[$settingKey];
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    public static function getCustomFile(string $filePath): array
    {
        if (!file_exists($filePath))
            throw new FileLostException();

        try
        {
            $jsonString = file_get_contents($filePath);
            $jsonArray = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        }
        catch (Exception $e) { throw new JSONDecodeException(); }

        return $jsonArray;
    }

    public static function getCustomReply(string $replyKey, ...$variables)
    {
        if (count($variables) == 0) return CUSTOM_REPLY[$replyKey];

        $reply = CUSTOM_REPLY[$replyKey];

        // Start from {&1}
        for ($index = 0; $index < count($variables); $index++)
            $reply = str_replace("{&" . ($index + 1) . "}", $variables[$index], $reply);

        return $reply;
    }

    public static function getCustomString(string $customString, ...$variables): string
    {
        if (count($variables) == 0) return $customString;

        // Start from {&1}
        for ($index = 0; $index < count($variables); $index++)
            $customString = str_replace("{&" . ($index + 1) . "}", $variables[$index], $customString);

        return $customString;
    }
}
