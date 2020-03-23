<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\Customization;
use DiceRobot\Exception\OrderErrorException;

/**
 * Send help information according to the template.
 */
final class Help extends AbstractAction
{
    public function __invoke(): void
    {
        $order = preg_replace("/^\.help/i", "", $this->message, 1);

        if ($order != "")
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new OrderErrorException;

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->reply = join("\n", Customization::getCustomFile(DICEROBOT_HELP_TEMPLATE_PATH));
    }
}
