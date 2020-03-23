<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\API;
use DiceRobot\Base\Customization;
use DiceRobot\Exception\OrderErrorException;

/**
 * Send greetings according to the template.
 */
final class Hello extends AbstractAction
{
    public function __invoke(): void
    {
        $order = preg_replace("/^\.hello/i", "", $this->message, 1);

        if ($order != "")
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new OrderErrorException;

        /** @noinspection PhpUnhandledExceptionInspection */
        $template = join("\n", Customization::getCustomFile(DICEROBOT_HELLO_TEMPLATE_PATH));

        $loginInfo = API::getLoginInfo();

        $this->reply = Customization::getCustomString($template, $loginInfo["data"]["nickname"],
            substr($this->selfId, -4), substr($this->selfId, -4));
    }
}
