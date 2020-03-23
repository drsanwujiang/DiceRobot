<?php
/**
 * Settings of DiceRobot.
 * Some of the settings can be changed according to your actual conditions, the other should NOT be modified generally.
 */

/** Port of HTTP API. */
const HTTP_API_PORT = 5700;

/** Custom settings. */
define("CUSTOM_SETTINGS", [
    /** Maximum of dice number, default value is 100 */
    "maxDiceNumber" => 100,

    /** Maximum of dice surface number, default value is 1000 */
    "maxSurfaceNumber" => 1000,

    /** Dice surface number when it's undefined in specific chat, default value is 100.*/
    "defaultSurfaceNumber" => 100,

    /** Maximum of character's attribute, default value is 1000.*/
    "maxAttribute" => 1000,

    /** Maximum of character's attribute change, default value is 1000.*/
    "maxAttributeChange" => 1000,

    /** Maximum count of COC or DND character card generation, default value is 20.*/
    "maxCharacterCardGenerateCount" => 20,

    /** Maximum times to repeat order, default value is 100.*/
    "maxRepeatTimes" => 100,

    /** Seed of .orz, default value is the timestamp of 00:00:00 today */
    "kowtowRandomSeed" => strtotime(date("Y-m-d")),

    /** Seed of .jrrp, default value is the timestamp of 00:00:00 today */
    "jrrpRandomSeed" => strtotime(date("Y-m-d"))
]);

/**
 * In general, you should stop modifying and save this file now. Enjoy your TRPG time~
 */

const HTTP_API_PATH = "localhost:" . HTTP_API_PORT;

/** URL of HTTP API. */
const HTTP_API_URL = [
    "getGroupInfo" => HTTP_API_PATH . "/get_group_info",
    "getGroupMemberInfo" => HTTP_API_PATH . "/get_group_member_info",
    "getLoginInfo" => HTTP_API_PATH . "/get_login_info",
    "sendDiscussMessage" => HTTP_API_PATH . "/send_discuss_msg",
    "sendGroupMessage" => HTTP_API_PATH . "/send_group_msg",
    "sendPrivateMessage" => HTTP_API_PATH . "/send_private_msg",
    "setDiscussLeave" => HTTP_API_PATH . "/set_discuss_leave",
    "setFriendAddRequest" => HTTP_API_PATH . "/set_friend_add_request",
    "setGroupAddRequest" => HTTP_API_PATH . "/set_group_add_request",
    "setGroupCard" => HTTP_API_PATH . "/set_group_card",
    "setGroupLeave" => HTTP_API_PATH . "/set_group_leave"
];

const CUSTOM_API_PATH = "https://api.drsanwujiang.com/dicerobot";

/** URL of custom API. */
const CUSTOM_API_URL = [
    "getAPICredential" => CUSTOM_API_PATH . "/get_credential",
    "getCharacterCard" => CUSTOM_API_PATH . "/get_character_card",
    "heartbeatReport" => CUSTOM_API_PATH . "/heartbeat_report",
    "queryDelinquentGroup" => CUSTOM_API_PATH . "/query_banned_group",
    "sanityCheck" => CUSTOM_API_PATH . "/sanity_check",
    "submitDelinquentGroup" => CUSTOM_API_PATH . "/add_banned_group",
    "updateCharacterCard" => CUSTOM_API_PATH . "/update_character_card"
];

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
const DICEROBOT_VERSION = "1.2.0";
