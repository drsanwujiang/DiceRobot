<?php
/**
 * Class autoloader.
 */

$map = [
    /* Classes of outermost application. */
    "DiceRobot\App" => __DIR__ . "/DiceRobot/App.php",
    "DiceRobot\Parser" => __DIR__ . "/DiceRobot/Parser.php",
    "DiceRobot\RouteCollector" => __DIR__ . "/DiceRobot/RouteCollector.php",

    /** Action classes. Remember to add you class below. */
    "DiceRobot\Action\Message\BindCard" => __DIR__ . "/DiceRobot/Action/Message/BindCard.php",
    "DiceRobot\Action\Message\CheckDice" => __DIR__ . "/DiceRobot/Action/Message/CheckDice.php",
    "DiceRobot\Action\Message\COC" => __DIR__ . "/DiceRobot/Action/Message/COC.php",
    "DiceRobot\Action\Message\Dice" => __DIR__ . "/DiceRobot/Action/Message/Dice.php",
    "DiceRobot\Action\Message\DND" => __DIR__ . "/DiceRobot/Action/Message/DND.php",
    "DiceRobot\Action\Message\Hello" => __DIR__ . "/DiceRobot/Action/Message/Hello.php",
    "DiceRobot\Action\Message\Help" => __DIR__ . "/DiceRobot/Action/Message/Help.php",
    "DiceRobot\Action\Message\JRRP" => __DIR__ . "/DiceRobot/Action/Message/JRRP.php",
    "DiceRobot\Action\Message\Kowtow" => __DIR__ . "/DiceRobot/Action/Message/Kowtow.php",
    "DiceRobot\Action\Message\Nickname" => __DIR__ . "/DiceRobot/Action/Message/Nickname.php",
    "DiceRobot\Action\Message\RobotCommandRouter" => __DIR__ . "/DiceRobot/Action/Message/RobotCommandRouter.php",
    "DiceRobot\Action\Message\Set" => __DIR__ . "/DiceRobot/Action/Message/Set.php",
    "DiceRobot\Action\Message\SetCOC" => __DIR__ . "/DiceRobot/Action/Message/SetCOC.php",
    "DiceRobot\Action\Message\RobotCommand\About" => __DIR__ . "/DiceRobot/Action/Message/RobotCommand/About.php",
    "DiceRobot\Action\Message\RobotCommand\Goodbye" => __DIR__ . "/DiceRobot/Action/Message/RobotCommand/Goodbye.php",
    "DiceRobot\Action\Message\RobotCommand\Nickname" => __DIR__ . "/DiceRobot/Action/Message/RobotCommand/Nickname.php",
    "DiceRobot\Action\Message\RobotCommand\Start" => __DIR__ . "/DiceRobot/Action/Message/RobotCommand/Start.php",
    "DiceRobot\Action\Message\RobotCommand\Stop" => __DIR__ . "/DiceRobot/Action/Message/RobotCommand/Stop.php",
    "DiceRobot\Action\MetaEvent\Heartbeat" => __DIR__ . "/DiceRobot/Action/MetaEvent/Heartbeat.php",
    "DiceRobot\Action\Notice\SelfAdded" => __DIR__ . "/DiceRobot/Action/Notice/SelfAdded.php",
    "DiceRobot\Action\Notice\SelfKicked" => __DIR__ . "/DiceRobot/Action/Notice/SelfKicked.php",
    "DiceRobot\Action\Request\FriendAdd" => __DIR__ . "/DiceRobot/Action/Request/FriendAdd.php",
    "DiceRobot\Action\Request\GroupInvite" => __DIR__ . "/DiceRobot/Action/Request/GroupInvite.php",

    /* Base and exception classes. */
    "DiceRobot\Base\AbstractAction" => __DIR__ . "/DiceRobot/Base/AbstractAction.php",
    "DiceRobot\Base\API" => __DIR__ . "/DiceRobot/Base/API.php",
    "DiceRobot\Base\CharacterCard" => __DIR__ . "/DiceRobot/Base/CharacterCard.php",
    "DiceRobot\Base\CheckDiceRule" => __DIR__ . "/DiceRobot/Base/CheckDiceRule.php",
    "DiceRobot\Base\Customization" => __DIR__ . "/DiceRobot/Base/Customization.php",
    "DiceRobot\Base\DiceOperation" => __DIR__ . "/DiceRobot/Base/DiceOperation.php",
    "DiceRobot\Base\DiceSubexpression" => __DIR__ . "/DiceRobot/Base/DiceSubexpression.php",
    "DiceRobot\Base\RobotCommandAction" => __DIR__ . "/DiceRobot/Base/RobotCommandAction.php",
    "DiceRobot\Base\RobotSettings" => __DIR__ . "/DiceRobot/Base/RobotSettings.php",
    "DiceRobot\Base\Rolling" => __DIR__ . "/DiceRobot/Base/Rolling.php",
    "DiceRobot\Exception\CharacterCardLostException" =>
        __DIR__ . "/DiceRobot/Exception/CharacterCardLostException.php",
    "DiceRobot\Exception\FileLostException" => __DIR__ . "/DiceRobot/Exception/FileLostException.php",
    "DiceRobot\Exception\InformativeException" => __DIR__ . "/DiceRobot/Exception/InformativeException.php",
    "DiceRobot\Exception\JSONDecodeException" => __DIR__ . "/DiceRobot/Exception/JSONDecodeException.php",
    "DiceRobot\Exception\OrderErrorException" => __DIR__ . "/DiceRobot/Exception/OrderErrorException.php",
    "DiceRobot\Exception\COCCheckException\COCCheckRuleDangerousException" =>
        __DIR__ . "/DiceRobot/Exception/COCCheckException/COCCheckRuleDangerousException.php",
    "DiceRobot\Exception\COCCheckException\COCCheckRuleInvalidException" =>
        __DIR__ . "/DiceRobot/Exception/COCCheckException/COCCheckRuleInvalidException.php",
    "DiceRobot\Exception\COCCheckException\COCCheckRuleLostException" =>
        __DIR__ . "/DiceRobot/Exception/COCCheckException/COCCheckRuleLostException.php",
    "DiceRobot\Exception\COCCheckException\COCCheckRuleMatchFailedException" =>
        __DIR__ . "/DiceRobot/Exception/COCCheckException/COCCheckRuleMatchFailedException.php",
];

spl_autoload_register(function ($class) use ($map) {
    if (isset($map[$class]))
        require($map[$class]);
}, true);
