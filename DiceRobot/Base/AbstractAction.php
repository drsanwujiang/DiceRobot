<?php
namespace DiceRobot\Base;

use DiceRobot\Parser;

/**
 * Class AbstractAction
 *
 * Parent class to all the action classes. Action class should extend this class and implement function __invoke().
 */
abstract class AbstractAction extends Parser
{
    protected string $userNickname;
    protected object $sender;

    public function __construct(object $eventData)
    {
        parent::__construct($eventData);

        if ($this->postType == "message")
        {
            $this->loadRobotSettings();
            $this->userNickname = $this->getNickname();
            $this->sender = $eventData->sender;
        }
    }

    abstract public function __invoke(): void;

    final protected function loadRobotSettings(?string $chatType = NULL, ?int $chatId = NULL): void
    {
        RobotSettings::setConfigFilePath($chatType ?? $this->chatType, $chatId ?? $this->chatId);
        RobotSettings::loadSettings();
    }

    final private function getNickname(): string
    {
        return RobotSettings::getNickname($this->userId) ?? $this->userName;
    }

    final protected function getRobotNickname(): string
    {
        return RobotSettings::getSetting("robotNickname") ?? API::getLoginInfo()["data"]["nickname"];
    }

    public function checkActive(): bool
    {
        if ($this->postType == "message")
        {
            $isActive = RobotSettings::getSetting("active");
            $isActive = $isActive ?? true;  // True by default

            if (!$isActive)
            {
                $this->noResponse();
                return false;
            }
        }

        return true;
    }
}
