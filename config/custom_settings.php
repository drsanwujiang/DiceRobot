<?php

use Monolog\Logger;

/**
 * 在这里填写机器人的 QQ 号，以及 Mirai API HTTP 插件中的 Auth Key
 */
$settings["mirai"] = [
    "robot" => [
        "id" => 10000,
        "authKey" => "12345678"
    ]
];

/**
 * 在这里设置日志等级，file 表示日志文件的等级，console 表示控制台日志的等级
 */
$settings["log"] = [
    "level" => [
        "file" => Logger::NOTICE,
        "console" => Logger::CRITICAL,
    ]
];

/**
 * 在这里自定义设置，目前支持以下选项的设置
 */
$settings["order"] = [
    /** 最大骰子个数，默认 100 */
    "maxDiceNumber" => 100,

    /** 最大骰子面数，默认 1000 */
    "maxSurfaceNumber" => 1000,

    /** 人物卡生成次数最大值，默认 20 */
    "maxCharacterCardGenerateCount" => 20,

    /** 指令重复次数最大值，默认 100 */
    "maxRepeatTimes" => 100,
];
