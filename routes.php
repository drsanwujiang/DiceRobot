<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */
/**
 * Routes of DiceRobot.
 */

use DiceRobot\App;

return function (App $app) {
    // Routes of message event
    $app->group("message", function (App $group) {
        $group->add("^\\.robot", \DiceRobot\Action\Message\RobotCommandRouter::class);
        $group->add("^\\.ra", \DiceRobot\Action\Message\Check::class);
        $group->add("^\\.r", \DiceRobot\Action\Message\Roll::class);
        $group->add("^\\.sc", \DiceRobot\Action\Message\SanCheck::class);
        $group->add("^\\.hp", \DiceRobot\Action\Message\ChangeAttribute::class);
        $group->add("^\\.mp", \DiceRobot\Action\Message\ChangeAttribute::class);
        $group->add("^\\.san", \DiceRobot\Action\Message\ChangeAttribute::class);
        $group->add("^\\.coc", \DiceRobot\Action\Message\COC::class);
        $group->add("^\\.dnd", \DiceRobot\Action\Message\DND::class);
        $group->add("^\\.jrrp", \DiceRobot\Action\Message\JRRP::class);
        $group->add("^\\.orz", \DiceRobot\Action\Message\Kowtow::class);
        $group->add("^\\.card", \DiceRobot\Action\Message\BindCard::class);
        $group->add("^\\.nn", \DiceRobot\Action\Message\Nickname::class);
        $group->add("^\\.setcoc", \DiceRobot\Action\Message\SetCOC::class);
        $group->add("^\\.set", \DiceRobot\Action\Message\Set::class);
        $group->add("^\\.help", \DiceRobot\Action\Message\Help::class);
        $group->add("^\\.hello", \DiceRobot\Action\Message\Hello::class);
    });

    // Routes of notice event
    $app->group("notice", function (App $group) {
        $group->addComparer([$group->noticeType, $group->userId], ["group_increase", $group->selfId],
            \DiceRobot\Action\Notice\SelfAdded::class);
        $group->addComparer([$group->noticeType, $group->subType], ["group_decrease", "kick_me"],
            \DiceRobot\Action\Notice\SelfKicked::class);
    });

    // Routes of request event
    $app->group("request", function (App $group) {
        $group->addComparer([$group->requestType], ["friend"],
            \DiceRobot\Action\Request\FriendAdd::class);
        $group->addComparer([$group->requestType, $group->subType], ["group", "invite"],
            \DiceRobot\Action\Request\GroupInvite::class);
    });

    // Routes of meta event
    $app->group("meta_event", function (App $group) {
        $group->addComparer([$group->metaEventType], ["heartbeat"],
            \DiceRobot\Action\MetaEvent\Heartbeat::class);
    });
};
