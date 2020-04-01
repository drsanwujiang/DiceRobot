<?php
/**
 * Default settings of DiceRobot. You should NOT modify this file, for it will be updated with DiceRobot.
 */

return [
    /** @var int Maximum of dice number, default value is 100 */
    "maxDiceNumber" => 100,

    /** @var int Maximum of dice surface number, default value is 1000 */
    "maxSurfaceNumber" => 1000,

    /** @var int Dice surface number when it's undefined in specific chat, default value is 100 */
    "defaultSurfaceNumber" => 100,

    /** @var int Maximum of character's attribute, default value is 1000 */
    "maxAttribute" => 1000,

    /** @var int Maximum of character's attribute change, default value is 1000 */
    "maxAttributeChange" => 1000,

    /** @var int Maximum count of COC or DND character card generation, default value is 20 */
    "maxCharacterCardGenerateCount" => 20,

    /** @var int Maximum times to repeat order, default value is 100 */
    "maxRepeatTimes" => 100,

    /** @var int Seed of .orz, default value is the timestamp of 00:00:00 today */
    "kowtowRandomSeed" => strtotime(date("Y-m-d")),

    /** @var int Seed of .jrrp, default value is the timestamp of 00:00:00 today */
    "jrrpRandomSeed" => strtotime(date("Y-m-d")),
];
