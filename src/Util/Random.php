<?php

declare(strict_types=1);

namespace DiceRobot\Util;

/**
 * Class Random
 *
 * Util class. Random number generator.
 *
 * @package DiceRobot\Util
 */
class Random
{
    /**
     * Generate random number(s).
     *
     * @param int $count Count of generation.
     * @param int $max The maximum.
     * @param int $min The minimum.
     *
     * @return array Generation result(s).
     */
    public static function generate(int $count = 1, int $max = 100, int $min = 1): array
    {
        mt_srand();

        $results = [];

        while ($count--) {
            $results[] = mt_rand($min, $max);
        }

        return $results;
    }

    /**
     * Generate random number with random seed.
     *
     * @param int $seed Random seed.
     * @param int $max The maximum.
     * @param int $min The minimum.
     *
     * @return int Generation result.
     */
    public static function generateWithSeed(int $seed, int $max = 100, int $min = 1): int
    {
        mt_srand($seed);

        // For the same seed and maximum, the random number is constant
        return mt_rand($min, $max);
    }

    /**
     * Draw items from an array.
     *
     * @param array $target Target array.
     * @param int $count Draw count.
     * @param string|null $glue Bound symbol.
     *
     * @return array|string Items.
     */
    // TODO: Declare union return type array|string in PHP 8
    public static function draw(array $target, int $count = 1, $glue = null)
    {
        mt_srand();

        $keys = array_rand($target, $count);
        $items = [];

        if (is_int($keys)) {
            $items[] = $target[$keys];
        } else {
            foreach ($keys as $key) {
                $items[] = $target[$key];
            }
        }

        if (is_null($glue)) {
            return $items;
        } else {
            return join($glue, $items);
        }
    }
}
