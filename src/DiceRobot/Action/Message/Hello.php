<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Exception\InformativeException\IOException\FileDecodeException;
use DiceRobot\Exception\InformativeException\IOException\FileLostException;
use DiceRobot\Exception\InformativeException\OrderErrorException;
use DiceRobot\Exception\InformativeException\ReferenceUndefinedException;
use DiceRobot\Service\Container\Reference;
use DiceRobot\Service\Customization;

/**
 * Send greetings according to the template.
 */
final class Hello extends Action
{
    /**
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws InternalErrorException
     * @throws NetworkErrorException
     * @throws OrderErrorException
     * @throws ReferenceUndefinedException
     */
    public function __invoke(): void
    {
        $order = preg_replace("/^\.hello/i", "", $this->message, 1);
        $this->checkOrder($order);

        $template = (new Reference("HelloTemplate"))->getString();
        $this->reply = Customization::getString($template, $this->coolq->getLoginInfo()["nickname"],
            substr($this->selfId, -4), substr($this->selfId, -4));
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
