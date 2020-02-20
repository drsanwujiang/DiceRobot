<?php
namespace DiceRobot;

/**
 * Class App
 *
 * Outermost application. Add routes and call methods defined in parent class.
 */
final class App extends RouteCollector
{
    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    public function addRoutes(): void
    {
        /** Add your actions in the corresponding methods below */

        // Add actions handling message event
        $this->group("message", function (RouteCollector $rc) {
            $rc->add(".robot", \DiceRobot\Action\Message\RobotCommandRouter::class);
            $rc->add(".ra", \DiceRobot\Action\Message\CheckDice::class);
            $rc->add(".r", \DiceRobot\Action\Message\Dice::class);
            $rc->add(".coc", \DiceRobot\Action\Message\COC::class);
            $rc->add(".dnd", \DiceRobot\Action\Message\DND::class);
            $rc->add(".jrrp", \DiceRobot\Action\Message\JRRP::class);
            $rc->add(".orz", \DiceRobot\Action\Message\Kowtow::class);
            $rc->add(".card", \DiceRobot\Action\Message\BindCard::class);
            $rc->add(".nn", \DiceRobot\Action\Message\Nickname::class);
            $rc->add(".setcoc", \DiceRobot\Action\Message\SetCOC::class);
            $rc->add(".set", \DiceRobot\Action\Message\Set::class);
            $rc->add(".help", \DiceRobot\Action\Message\Help::class);
            $rc->add(".hello", \DiceRobot\Action\Message\Hello::class);
        });

        // Add actions handling notice event
        $this->group("notice", function (RouteCollector $rc) {
            $rc->addComparer([$this->noticeType, $this->userId], ["group_increase", $this->selfId],
                \DiceRobot\Action\Notice\SelfAdded::class);
            $rc->addComparer([$this->noticeType, $this->subType], ["group_decrease", "kick_me"],
                \DiceRobot\Action\Notice\SelfKicked::class);
        });

        //Add actions handling request event
        $this->group("request", function (RouteCollector $rc) {
            $rc->addComparer([$this->requestType], ["friend"],
                \DiceRobot\Action\Request\FriendAdd::class);
            $rc->addComparer([$this->requestType, $this->subType], ["group", "invite"],
                \DiceRobot\Action\Request\GroupInvite::class);
        });

        //Add actions handling meta event
        $this->group("meta_event", function (RouteCollector $rc) {
            $rc->addComparer([$this->metaEventType], ["heartbeat"],
                \DiceRobot\Action\MetaEvent\Heartbeat::class);
        });
    }
}
