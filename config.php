<?php

use DiceRobot\Service\APIService;
use DiceRobot\Service\Container\ChatSettings;
use DiceRobot\Service\Container\CharacterCard;
use DiceRobot\Service\Container\Reference;
use DiceRobot\Service\Customization;

/** DiceRobot version. */
const DICEROBOT_VERSION = "1.3.0";

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

// Set up HTTP API URLs
APIService::setHttpApiUrl([
    "getGroupInfo" => "http://localhost:" . HTTP_API_PORT . "/get_group_info",
    "getGroupMemberInfo" => "http://localhost:" . HTTP_API_PORT . "/get_group_member_info",
    "getLoginInfo" => "http://localhost:" . HTTP_API_PORT . "/get_login_info",
    "sendDiscussMessage" => "http://localhost:" . HTTP_API_PORT . "/send_discuss_msg",
    "sendGroupMessage" => "http://localhost:" . HTTP_API_PORT . "/send_group_msg",
    "sendPrivateMessage" => "http://localhost:" . HTTP_API_PORT . "/send_private_msg",
    "setDiscussLeave" => "http://localhost:" . HTTP_API_PORT . "/set_discuss_leave",
    "setFriendAddRequest" => "http://localhost:" . HTTP_API_PORT . "/set_friend_add_request",
    "setGroupAddRequest" => "http://localhost:" . HTTP_API_PORT . "/set_group_add_request",
    "setGroupCard" => "http://localhost:" . HTTP_API_PORT . "/set_group_card",
    "setGroupLeave" => "http://localhost:" . HTTP_API_PORT . "/set_group_leave"
]);

// Set up custom API URLs
APIService::setCustomApiUrl([
    "getAPICredential" => "https://api.drsanwujiang.com/dicerobot/get_credential",
    "getCharacterCard" => "https://api.drsanwujiang.com/dicerobot/get_character_card",
    "heartbeatReport" => "https://api.drsanwujiang.com/dicerobot/heartbeat_report",
    "queryDelinquentGroup" => "https://api.drsanwujiang.com/dicerobot/query_banned_group",
    "sanityCheck" => "https://api.drsanwujiang.com/dicerobot/sanity_check",
    "submitDelinquentGroup" => "https://api.drsanwujiang.com/dicerobot/add_banned_group",
    "updateCharacterCard" => "https://api.drsanwujiang.com/dicerobot/update_character_card"
]);

// Set default port of HTTP API
if (!defined("HTTP_API_PORT"))
    define("HTTP_API_PORT", 5700);
