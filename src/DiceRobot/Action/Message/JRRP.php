<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException\OrderErrorException;
use DiceRobot\Service\Customization;
use DiceRobot\Service\Rolling;

/**
 * Send luck value of message sender.
 */
final class JRRP extends Action
{
    /**
     * @throws OrderErrorException
     */
    public function __invoke(): void
    {
        $order = preg_replace("/^\.jrrp/i", "", $this->message, 1);
        $this->checkOrder($order);

        $randomSeed = Customization::getSetting("jrrpRandomSeed") + (int) $this->userId;
        $jrrp = Rolling::rollBySeed($randomSeed, 1, 100)[0];
        $this->reply = Customization::getReply("jrrpReply", $this->userNickname, $jrrp);
    }

    /**
     * Check the validity of the order.
     *
     * @param string $order The order
     *
     * @throws OrderErrorException
     */
    private function checkOrder(string $order): void
    {
        if ($order != "")
            throw new OrderErrorException;
    }
}
