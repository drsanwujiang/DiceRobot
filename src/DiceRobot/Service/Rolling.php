<?php
namespace DiceRobot\Service;

/**
 * Random number generator.
 */
class Rolling
{
    public static function roll(int $diceNumber = 1, int $surfaceNumber = 100): array
    {
        $rollResult = [];

        for($i = 0; $i < $diceNumber; $i++)
        {
            $result = mt_rand(1, $surfaceNumber);
            array_push($rollResult, $result);
        }

        return $rollResult;
    }

    public static function rollBySeed($seed, int $diceNumber = 1, int $surfaceNumber = 100): array
    {
        mt_srand($seed);
        $rollResult = [];

        for($i = 0; $i < $diceNumber; $i++)
        {
            $result = mt_rand(1, $surfaceNumber);
            array_push($rollResult, $result);
        }

        return $rollResult;
    }
}
