<?php

use DiceRobot\Service\API\CoolQAPI;
use DiceRobot\Service\API\DiceRobotAPI;
use DiceRobot\Service\Container\ChatSettings;
use DiceRobot\Service\Container\CharacterCard;
use DiceRobot\Service\Container\Reference;
use DiceRobot\Service\Customization;

/** DiceRobot version. */
const DICEROBOT_VERSION = "1.4.0";

// Set up settings
Customization::setSettings(require __DIR__ . "/default/settings.php");
Customization::setCustomSettings(require __DIR__ . "/custom_settings.php");

// Set up wording
Customization::setWording(require __DIR__ . "/default/wording.php");

// Set up reply
Customization::setReply(require __DIR__ . "/default/reply.php");
Customization::setCustomReply(require __DIR__ . "/custom_reply.php");

// Set up file dirs
ChatSettings::setDir(__DIR__ . "/config/");
CharacterCard::setDir(__DIR__ . "/card/");
Reference::setDir(__DIR__ . "/reference/");

// Set up reference mapping
Reference::setMapping([
    "COCCheckRule" => "COCCheckRule.json",
    "COCCharacterCardTemplate" => "COCCharacterCardTemplate.json",
    "DNDCharacterCardTemplate" => "DNDCharacterCardTemplate",
    "AboutTemplate" => "AboutTemplate.json",
    "HelloTemplate" => "HelloTemplate.json",
    "HelpTemplate" => "HelpTemplate.json",
]);

// Set default port of HTTP API
if (!defined("HTTP_API_PORT"))
    define("HTTP_API_PORT", 5700);

// Set up API prefix
CoolQAPI::setPrefix("http://localhost:" . HTTP_API_PORT);
DiceRobotAPI::setPrefix("https://api.drsanwujiang.com/dicerobot/v2");
