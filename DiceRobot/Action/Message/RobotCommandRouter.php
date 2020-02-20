<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action\Message\RobotCommand\About;
use DiceRobot\Action\Message\RobotCommand\Goodbye;
use DiceRobot\Action\Message\RobotCommand\Nickname;
use DiceRobot\Action\Message\RobotCommand\Start;
use DiceRobot\Action\Message\RobotCommand\Stop;
use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\Customization;
use DiceRobot\Base\RobotSettings;

/**
 * Class RobotCommand
 *
 * Action class of order ".robot". Parse robot control order and pass to specific class to handle.
 */
final class RobotCommandRouter extends AbstractAction
{
    private object $eventData;

    private string $commandKey;
    private string $commandValue;

    public function __construct(object $eventData)
    {
        parent::__construct($eventData);

        $this->eventData = $eventData;

        $command = preg_replace("/^\.robot[\s]*/i", "", $this->message, 1);
        $commandPair = explode(" ", $command, 2);
        $this->commandKey = $commandPair[0];
        $this->commandValue = isset($commandPair[1]) ? trim($commandPair[1]) : "";
    }

    public function __invoke(): void
    {
        if ($this->commandKey == "about")
            $commandAction = new About($this->eventData, $this->commandValue);
        elseif ($this->commandKey == "start")
            $commandAction = new Start($this->eventData, $this->commandValue);
        elseif ($this->commandKey == "stop")
            $commandAction = new Stop($this->eventData, $this->commandValue);
        elseif ($this->commandKey == "nn")
            $commandAction = new Nickname($this->eventData, $this->commandValue);
        elseif ($this->commandKey == "goodbye")
            $commandAction = new Goodbye($this->eventData, $this->commandValue);

        if (isset($commandAction))
        {
            $commandAction();

            $this->reply = $commandAction->getReply();
            $this->atSender = $commandAction->getAtSender();
            $this->block = $commandAction->getBlock();
            $this->httpCode = $commandAction->getHttpCode();
        }
        else
            $this->unableToResolve();
    }

    public function checkActive(): bool
    {
        if ($this->commandKey == "start" || $this->commandKey == "goodbye")
            return true;

        $isActive = RobotSettings::getSetting("active");
        $isActive = is_null($isActive) ? true : $isActive;  // True by default

        if (!$isActive)
        {
            $this->noResponse();
            return false;
        }

        return true;
    }

    protected function unableToResolve(): void
    {
        $this->reply = Customization::getCustomReply("robotCommandUnknown");
    }
}
