<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\Customization;
use DiceRobot\Base\Rolling;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class JRRP
 *
 * Action class of order ".jrrp". Send luck value of message sender.
 */
final class JRRP extends AbstractAction
{
    public function __invoke(): void
    {
        $order = preg_replace("/^\.jrrp/i", "", $this->message, 1);

        if ($order != "")
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new OrderErrorException;

        $randomSeed = CUSTOM_SETTINGS["jrrpRandomSeed"] + intval($this->userId);
        $jrrpValue = Rolling::rollBySeed($randomSeed, 1, 100)[0];
        $this->reply = Customization::getCustomReply("jrrpReply", $this->userNickname, $jrrpValue);
    }
}
