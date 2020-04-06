<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException\IOException\FileDecodeException;
use DiceRobot\Exception\InformativeException\IOException\FileLostException;
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
     * @throws FileDecodeException
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
