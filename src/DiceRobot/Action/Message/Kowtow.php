<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action\Action;
use DiceRobot\Exception\InformativeException\OrderErrorException;
use DiceRobot\Service\Customization;
use DiceRobot\Service\Rolling;

/**
 * Kowtow to the robot, and it will show your devoutness~
 */
final class Kowtow extends Action
{
    private const KOWTOW_LEVEL = [10, 30, 60, 80, 95, 100];

    /**
     * @throws OrderErrorException
     */
    public function __invoke(): void
    {
        $order = preg_replace("/^\.orz/i", "", $this->message, 1);
        $this->checkOrder($order);

        $randomSeed = Customization::getSetting("kowtowRandomSeed") + (int) $this->userId;
        $piety = Rolling::rollBySeed($randomSeed, 1, 100)[0];
        $this->reply = Customization::getReply("kowtowHeading", $this->getRobotNickname(),
            $piety);

        for ($level = 0; $level < count(self::KOWTOW_LEVEL); $level++)
        {
            if ($piety <= self::KOWTOW_LEVEL[$level])
            {
                $this->reply .= Customization::getReply("kowtowLevel" . ($level + 1),
                    $this->getRobotNickname());
                break;
            }
        }

        $this->atSender = true;
    }

    /**
     * Check the validity of the order.
     *
     * @param string $order Order
     *
     * @throws OrderErrorException
     */
    private function checkOrder(string $order): void
    {
        if ($order != "")
            throw new OrderErrorException;
    }
}
