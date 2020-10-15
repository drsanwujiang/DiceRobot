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
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    public static function toCustomInstance(object $object, string $className, string $default)
    {
        $instance = new $className();
        $ref = new ReflectionClass($className);

        try
        {
            foreach ($ref->getProperties() as $property)
            {
                $name = $property->getName();

                if (isset($object->$name))
                    if ($property->getType()->isBuiltin())
                        $instance->$name = $object->$name;
                    else
                        $instance->$name =
                            static::toCustomInstance($object->$name, (string) $property->getType(), $default);
            }
        }
        // TODO: catch (Exception) in PHP 8
        catch (Exception $e)
        {
            if (class_exists($default))
                return new $default();

            return NULL;
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
        foreach ($variables as $variable => $value)
            $string = str_replace("{&{$variable}}", $value, $string);

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

        foreach ($matches as $match)
                $messageChain[] = FragmentFactory::fromMiraiCode($match)->toMessage();

        return $messageChain;
    }
}
