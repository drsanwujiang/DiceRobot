<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\Customization;
use DiceRobot\Base\Rolling;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class Kowtow
 *
 * Action class of order ".orz". Kowtow to the robot, and it will show your devoutness~
 */
final class Kowtow extends AbstractAction
{
    private const KOWTOW_LEVEL = [10, 30, 60, 80, 95, 100];

    public function __invoke(): void
    {
        $order = preg_replace("/^\.orz/i", "", $this->message, 1);

        if ($order != "")
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new OrderErrorException;

        $randomSeed = CUSTOM_SETTINGS["kowtowRandomSeed"] + intval($this->userId);
        $pietyValue = Rolling::rollBySeed($randomSeed, 1, 100)[0];
        $welcomeText = Customization::getCustomReply("kowtowWelcome", $this->getRobotNickname(),
            $pietyValue);
        $resultText = "";

        for ($level = 0; $level < count(self::KOWTOW_LEVEL); $level++)
        {
            if ($pietyValue <= self::KOWTOW_LEVEL[$level])
            {
                $resultText = Customization::getCustomReply("kowtowLevel" . ($level + 1),
                    $this->getRobotNickname());
                break;
            }
        }

        $this->reply = $welcomeText . $resultText;
        $this->atSender = true;
    }
}
