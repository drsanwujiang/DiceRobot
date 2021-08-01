<?php

declare(strict_types=1);

namespace DiceRobot\Util;

use DiceRobot\Factory\FragmentFactory;
use DiceRobot\Interfaces\Fragment\ParsableFragment;
use Exception;
use ReflectionClass;

/**
 * Class Convertor
 *
 * Util class. Data convertor.
 *
 * @package DiceRobot\Util
 */
class Convertor
{
    /**
     * Convert object to the custom instance.
     *
     * @param object $object Source object.
     * @param string $className Class name of the custom instance.
     * @param string $default Default class name.
     *
     * @return mixed|null Custom instance when succeed, or null when failed.
     */
    public static function toCustomInstance(object $object, string $className, string $default)
    {
        try {
            $ref = new ReflectionClass($className);
            $instance = new $className();

            foreach ($ref->getProperties() as $property) {
                $name = $property->getName();

                if (isset($object->$name)) {
                    $type = $property->getType();

                    if ($type->isBuiltin()) {
                        settype($object->$name, (string) $type);
                        $instance->$name = $object->$name;
                    } else {
                        $instance->$name =
                            static::toCustomInstance($object->$name, (string) $property->getType(), $default);
                    }
                }
            }
        } catch (Exception $e) {  // TODO: catch (Exception) in PHP 8
            if (class_exists($default)) {
                return new $default();
            }

            return null;
        }

        return $instance;
    }

    /**
     * Convert string with parameters to plain text string.
     *
     * @param string $string String to be replaced.
     * @param string[] $variables Variables to replace with.
     *
     * @return string Replaced string.
     */
    public static function toCustomString(string $string, array $variables = []): string
    {
        foreach ($variables as $variable => $value) {
            $string = str_replace("{&{$variable}}", $value, $string);
        }

        return $string;
    }

    /**
     * Convert string-typed messages (may be with Mirai code) to parsable fragments.
     *
     * @param string $messages String-typed messages.
     *
     * @return ParsableFragment[] Parsed parsable fragments.
     */
    public static function toFragments(string $messages): array
    {
        $matches = preg_split(
            "/(\[mirai:\w+:.+?])/i",
            $messages,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );
        $fragments = [];

        foreach ($matches as $match) {
            $fragments[] = FragmentFactory::fromMiraiCode($match);
        }

        return $fragments;
    }

    /**
     * Convert string-typed messages (may be with Mirai code) to message chain.
     *
     * @param string $messages String-typed messages.
     *
     * @return array Parsed message chain.
     */
    public static function toMessageChain(string $messages): array
    {
        $fragments = self::toFragments($messages);
        $messageChain = [];

        foreach ($fragments as $fragment) {
            $messageChain[] = $fragment->toMessage();
        }

        return $messageChain;
    }
}
