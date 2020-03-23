<?php
namespace DiceRobot\Base;

use DiceRobot\Parser;

/**
 * Abstract action. Action class should extend this class and implement function __invoke().
 */
abstract class AbstractAction extends Parser
{
    protected string $userNickname;
    protected object $sender;

    public function __construct(object $eventData)
    {
        $this->parseEventData($eventData);

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

    /**
     * Actions should call this method when no need to response. For accurate recognition, this function should
     * always set HTTP code to 204.
     */
    final protected function noResponse(): void
    {
        $this->httpCode = 204;
    }

    /**
     * Action can redefine this method to implement specific function, and call it when the order is unable to be
     * resolved.
     */
    protected function unableToResolve(): void
    {
        $this->httpCode = 204;
    }
}
