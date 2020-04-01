<?php
namespace DiceRobot\Service;

/**
 * Utility class with methods to get customized data.
 */
final class Customization
{
    private static array $settings;
    private static array $wording;
    private static array $reply;

    /**
     * Set the global settings of DiceRobot.
     *
     * @param array $settings The global settings
     */
    public static function setSettings(array $settings): void
    {
        self::$settings = $settings;
    }

    /**
     * Set the global settings modified by robot owner.
     *
     * @param array $settings The global settings modified
     */
    public static function setCustomSettings(array $settings): void
    {
        array_replace(self::$settings, $settings);
    }

    /**
     * Set the wordings used in DiceRobot.
     *
     * @param array $wording The wordings
     */
    public static function setWording(array $wording): void
    {
        self::$wording = $wording;
    }

    /**
     * Set the reply of DiceRobot.
     *
     * @param array $reply The reply
     */
    public static function setReply(array $reply): void
    {
        self::$reply = $reply;
    }

    /**
     * Set the reply modified by robot owner.
     *
     * @param array $reply The reply modified
     */
    public static function setCustomReply(array $reply): void
    {
        array_replace(self::$reply, $reply);
    }

    /**
     * Get robot setting.
     *
     * @param string $key Setting key
     *
     * @return mixed Setting value
     */
    public static function getSetting(string $key)
    {
        return self::$settings[$key];
    }

    /**
     * Get the wording.
     *
     * @param string $name Wordings name
     * @param mixed $key Wording key
     *
     * @return string
     */
    public static function getWording(string $name, $key): string
    {
        return self::$wording[$name][$key];
    }

    /**
     * Get the reply.
     *
     * @param string $replyKey Reply key
     * @param mixed ...$variables Parameters to replace with
     *
     * @return string
     */
    public static function getReply(string $replyKey, ...$variables): string
    {
        if (count($variables) == 0)
            return self::$reply[$replyKey];

        $reply = self::$reply[$replyKey];

        // Start from {&1}
        for ($index = 0; $index < count($variables); $index++)
            $reply = str_replace("{&" . ($index + 1) . "}", $variables[$index], $reply);

        return $reply;
    }

    /**
     * Get the custom string .
     *
     * @param string $string String to be replaced
     * @param mixed ...$variables Parameters to replace with
     *
     * @return string
     */
    public static function getString(string $string, ...$variables): string
    {
        if (count($variables) == 0)
            return $string;

        // Start from {&1}
        for ($index = 0; $index < count($variables); $index++)
            $string = str_replace("{&" . ($index + 1) . "}", $variables[$index], $string);

        return $string;
    }
}
