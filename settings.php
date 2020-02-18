<?php
/**
 * This file contains all the settings of DiceRobot. Some of the settings can be changed according to your actual
 * conditions, the other should NOT be modified generally.
 */

/** Custom settings. */
define("CUSTOM_SETTINGS", array(
    /** Maximum of dice number, default value is 100 */
    "maxDiceNumber" => 100,

    /** Maximum of dice surface number, default value is 1000 */
    "maxSurfaceNumber" => 1000,

    /** Dice surface number when it's undefined in specific chat, default value is 100.*/
    "defaultSurfaceNumber" => 100,

    /** Maximum of character's attribute, default value is 1000.*/
    "maxAttribute" => 1000,

    /** Maximum count of COC or DND character card generation, default value is 20.*/
    "maxCharacterCardGenerateCount" => 20,

    /** Seed of .orz, default value is the timestamp of 00:00:00 today */
    "kowtowRandomSeed" => strtotime(date("Y-m-d")),

    /** Seed of .jrrp, default value is the timestamp of 00:00:00 today */
    "jrrpRandomSeed" => strtotime(date("Y-m-d"))
));

/** Port of HTTP API. */
const HTTP_API_PORT = 5700;

/**
 * In general, you should stop modifying and save this file now. Enjoy your TRPG time~
 */

/** URL of HTTP API. */
const HTTP_API_URL = array(
    "getGroupInfo" => "localhost:" . HTTP_API_PORT . "/get_group_info",
    "getGroupMemberInfo" => "localhost:" . HTTP_API_PORT . "/get_group_member_info",
    "getLoginInfo" => "localhost:" . HTTP_API_PORT . "/get_login_info",
    "sendDiscussMessage" => "localhost:" . HTTP_API_PORT . "/send_discuss_msg",
    "sendGroupMessage" => "localhost:" . HTTP_API_PORT . "/send_group_msg",
    "sendPrivateMessage" => "localhost:" . HTTP_API_PORT . "/send_private_msg",
    "setDiscussLeave" => "localhost:" . HTTP_API_PORT . "/set_discuss_leave",
    "setFriendAddRequest" => "localhost:" . HTTP_API_PORT . "/set_friend_add_request",
    "setGroupAddRequest" => "localhost:" . HTTP_API_PORT . "/set_group_add_request",
    "setGroupCard" => "localhost:" . HTTP_API_PORT . "/set_group_card",
    "setGroupLeave" => "localhost:" . HTTP_API_PORT . "/set_group_leave"
);

const CUSTOM_API_PATH = "https://api.drsanwujiang.com/dicerobot";

/** URL of custom API. */
const CUSTOM_API_URL = array(
    "getAPICredential" => CUSTOM_API_PATH . "/get_credential",
    "getCharacterCard" => CUSTOM_API_PATH . "/get_character_card",
    "heartbeatReport" => CUSTOM_API_PATH . "/heartbeat_report",
    "queryDelinquentGroup" => CUSTOM_API_PATH . "/query_banned_group",
    "submitDelinquentGroup" => CUSTOM_API_PATH . "/add_banned_group"
);

/** Path of config files folder. */
const CONFIG_DIR_PATH = __DIR__ . "/config/";
/** Path of character cards folder. */
const CHARACTER_CARD_DIR_PATH = __DIR__ . "/card/";
/** Path of reference files folder. */
const REFERENCE_DIR_PATH = __DIR__ . "/Reference/";

/** Path of referenced files. */
const COC_CHECK_DICE_RULE_PATH = REFERENCE_DIR_PATH . "COCCheckDiceRule.json";
const COC_CHARACTER_CARD_TEMPLATE_PATH = REFERENCE_DIR_PATH . "COCCharacterCardTemplate.json";
const DND_CHARACTER_CARD_TEMPLATE_PATH = REFERENCE_DIR_PATH . "DNDCharacterCardTemplate.json";
const DICEROBOT_ABOUT_TEMPLATE_PATH = REFERENCE_DIR_PATH . "AboutTemplate.json";
const DICEROBOT_HELLO_TEMPLATE_PATH = REFERENCE_DIR_PATH . "HelloTemplate.json";
const DICEROBOT_HELP_TEMPLATE_PATH = REFERENCE_DIR_PATH . "HelpTemplate.json";

/** DiceRobot version. */
const DICEROBOT_VERSION = "1.1.0";
