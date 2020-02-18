<?php
namespace DiceRobot;

/**
 * Class App
 *
 * Outermost application. Add routes and call methods defined in parent class.
 */
final class App extends RouteCollector
{
    public function addRoutes(): void
    {
        /** Add your actions in the corresponding methods below */

        // Add actions handling message event
        $this->group("message", function (RouteCollector $rc) {
            $rc->add(".robot", "DiceRobot\Action\Message\RobotCommandRouter");
            $rc->add(".ra", "DiceRobot\Action\Message\CheckDice");
            $rc->add(".r", "DiceRobot\Action\Message\Dice");
            $rc->add(".coc", "DiceRobot\Action\Message\COC");
            $rc->add(".dnd", "DiceRobot\Action\Message\DND");
            $rc->add(".jrrp", "DiceRobot\Action\Message\JRRP");
            $rc->add(".orz", "DiceRobot\Action\Message\Kowtow");
            $rc->add(".card", "DiceRobot\Action\Message\BindCard");
            $rc->add(".nn", "DiceRobot\Action\Message\Nickname");
            $rc->add(".setcoc", "DiceRobot\Action\Message\SetCOC");
            $rc->add(".set", "DiceRobot\Action\Message\Set");
            $rc->add(".help", "DiceRobot\Action\Message\Help");
            $rc->add(".hello", "DiceRobot\Action\Message\Hello");
        });

        // Add actions handling notice event
        $this->group("notice", function (RouteCollector $rc) {
            $rc->addComparer([$this->noticeType, $this->userId], ["group_increase", $this->selfId],
                "DiceRobot\Action\Notice\SelfAdded");
            $rc->addComparer([$this->noticeType, $this->subType], ["group_decrease", "kick_me"],
                "DiceRobot\Action\Notice\SelfKicked");
        });

        //Add actions handling request event
        $this->group("request", function (RouteCollector $rc) {
            $rc->addComparer([$this->requestType], ["friend"],
                "DiceRobot\Action\Request\FriendAdd");
            $rc->addComparer([$this->requestType, $this->subType], ["group", "invite"],
                "DiceRobot\Action\Request\GroupInvite");
        });

        //Add actions handling meta event
        $this->group("meta_event", function (RouteCollector $rc) {
            $rc->addComparer([$this->metaEventType], ["heartbeat"],
                "DiceRobot\Action\MetaEvent\Heartbeat");
        });
    }
}
