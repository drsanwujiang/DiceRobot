<?php

namespace DiceRobot\Util;

use DiceRobot\Factory\FragmentFactory;
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
     * Convert JSON object to the custom object.
     *
     * @param object $object
     * @param string $className
     * @param string $default
     *
     * @return mixed|null
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
                        settype($object->$name, $type);
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
     * @param string $string
     * @param array $variables
     *
     * @return string
     */
    public static function toCustomString(string $string, array $variables = []): string
    {
        foreach ($variables as $variable => $value) {
            $string = str_replace("{&{$variable}}", $value, $string);
        }

        return $string;
    }

    /**
     * Convert string (with Mirai code) to message chain.
     *
     * @param string $message
     *
     * @return string[]
     */
    public static function toMessageChain(string $message): array
    {
        $matches = preg_split(
            "/(\[mirai:\w+:.+?])/i",
            $message,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );
        $messageChain = [];

        foreach ($matches as $match) {
            $messageChain[] = FragmentFactory::fromMiraiCode($match)->toMessage();
        }

        return $messageChain;
    }
}
