<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Action;
use DiceRobot\Action\Message\RobotCommand\About;
use DiceRobot\Action\Message\RobotCommand\Goodbye;
use DiceRobot\Action\Message\RobotCommand\Nickname;
use DiceRobot\Action\Message\RobotCommand\Start;
use DiceRobot\Action\Message\RobotCommand\Stop;
use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Exception\InformativeException\IOException\FileDecodeException;
use DiceRobot\Exception\InformativeException\IOException\FileLostException;
use DiceRobot\Exception\InformativeException\IOException\FileUnwritableException;
use DiceRobot\Exception\InformativeException\ReferenceUndefinedException;
use DiceRobot\Service\Customization;

/**
 * Parse robot control order and pass to specific class to handle.
 */
final class RobotCommandRouter extends Action
{
    private object $eventData;

    private string $commandKey;
    private string $commandValue;

    /**
     * The  constructor.
     *
     * @param object $eventData The event data
     *
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws FileUnwritableException
     */
    public function __construct(object $eventData)
    {
        parent::__construct($eventData);

        $this->eventData = $eventData;

        $command = preg_replace("/^\.robot[\s]*/i", "", $this->message, 1);

        // Parse the order
        preg_match("/^([\S]+)(?:[\s]+([\S]+))?$/", $command, $matches);
        $this->commandKey = $matches[1] ?? "";
        $this->commandValue = $matches[2] ?? "";
    }

    /**
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws FileUnwritableException
     * @throws InternalErrorException
     * @throws NetworkErrorException
     * @throws ReferenceUndefinedException
     */
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

        $isActive = $this->chatSettings->get("active");
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
        $this->reply = Customization::getReply("robotCommandUnknown");
    }
}
