<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action\Action;
use DiceRobot\Exception\InformativeException\FileLostException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;
use DiceRobot\Exception\InformativeException\OrderErrorException;
use DiceRobot\Exception\InformativeException\ReferenceUndefinedException;
use DiceRobot\Service\APIService;
use DiceRobot\Service\Container\Reference;
use DiceRobot\Service\Customization;

/**
 * Send greetings according to the template.
 */
final class Hello extends Action
{
    /**
     * @throws FileLostException
     * @throws JSONDecodeException
     * @throws OrderErrorException
     * @throws ReferenceUndefinedException
     */
    public function __invoke(): void
    {
        $order = preg_replace("/^\.hello/i", "", $this->message, 1);
        $this->checkOrder($order);

        $template = (new Reference("HelloTemplate"))->getString();
        $loginInfo = $this->getLoginInfo();
        $this->reply = Customization::getString($template,
            $loginInfo["nickname"], substr($this->selfId, -4), substr($this->selfId, -4));
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

    /**
     * Get current robot's info.
     *
     * @return array Info
     */
    private function getLoginInfo(): array
    {
        return APIService::getLoginInfo()["data"];
    }
}
