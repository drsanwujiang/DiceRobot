<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action\Action;
use DiceRobot\Exception\InformativeException\FileLostException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;
use DiceRobot\Exception\InformativeException\OrderErrorException;
use DiceRobot\Exception\InformativeException\ReferenceUndefinedException;
use DiceRobot\Service\Container\Reference;

/**
 * Send help information according to the template.
 */
final class Help extends Action
{
    /**
     * @throws FileLostException
     * @throws JSONDecodeException
     * @throws OrderErrorException
     * @throws ReferenceUndefinedException
     */
    public function __invoke(): void
    {
        $order = preg_replace("/^\.help/i", "", $this->message, 1);
        $this->checkOrder($order);
        $this->reply = (new Reference("HelpTemplate"))->getString();
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
