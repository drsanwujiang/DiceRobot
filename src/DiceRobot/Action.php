<?php
namespace DiceRobot;

use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Exception\InformativeException\IOException\FileDecodeException;
use DiceRobot\Exception\InformativeException\IOException\FileLostException;
use DiceRobot\Exception\InformativeException\IOException\FileUnwritableException;
use DiceRobot\Service\API\CoolQAPI;
use DiceRobot\Service\API\DiceRobotAPI;
use DiceRobot\Service\Container\Dice\Dice;
use DiceRobot\Service\Container\ChatSettings;

/**
 * DiceRobot action. Action class should extend this class and implement function __invoke().
 */
abstract class Action extends Parser
{
    protected CoolQAPI $coolq;
    protected DiceRobotAPI $apiService;
    protected ChatSettings $chatSettings;
    protected string $userNickname;
    protected object $sender;

    /**
     * The constructor.
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

        $this->coolq = new CoolQAPI();
        $this->apiService = new DiceRobotAPI();

        if ($this->postType == "message")
        {
            $this->loadChatSettings();
            $this->userNickname = $this->getNickname();
            $this->sender = $eventData->sender;
        }
    }

    abstract public function __invoke(): void;

    /**
     * Load chat settings and initialize some class.
     *
     * @param string|null $chatType Chat type
     * @param int|null $chatId Chat ID
     *
     * @throws FileDecodeException
     * @throws FileLostException
     * @throws FileUnwritableException
     */
    final protected function loadChatSettings(?string $chatType = NULL, ?int $chatId = NULL): void
    {
        $this->chatSettings = new ChatSettings($chatType ?? $this->chatType, $chatId ?? $this->chatId);
        Dice::setDefaultSurfaceNumber($this->chatSettings->get("defaultSurfaceNumber"));
    }

    /**
     * Get user's nickname.
     *
     * @return string User nickname
     */
    final private function getNickname(): string
    {
        return $this->chatSettings->getNickname($this->userId) ?? $this->userName;
    }

    /**
     * Get robot's nickname
     *
     * @return string Robot nickname
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    final protected function getRobotNickname(): string
    {
        return $this->chatSettings->get("robotNickname") ?? $this->coolq->getLoginInfo()["nickname"];
    }

    /**
     * Check if the function is active. This method will be called by App.
     *
     * @return bool Active flag
     */
    public function checkActive(): bool
    {
        if ($this->postType == "message")
        {
            $isActive = $this->chatSettings->get("active");
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
