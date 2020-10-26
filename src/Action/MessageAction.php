<?php

declare(strict_types=1);

namespace DiceRobot\Action;

use DiceRobot\Data\Report\Message;
use DiceRobot\Data\Report\Contact\FriendSender;
use DiceRobot\Data\Report\Message\{FriendMessage, GroupMessage, TempMessage};
use DiceRobot\Data\Resource\ChatSettings;
use DiceRobot\Exception\MiraiApiException;
use DiceRobot\Interfaces\Action;
use DiceRobot\Service\{ApiService, ResourceService, RobotService};
use DiceRobot\Util\Convertor;
use Selective\Config\Configuration;

/**
 * Class MessageAction
 *
 * Action that responds to message report.
 *
 * @order
 *
 * @package DiceRobot\Action
 */
abstract class MessageAction implements Action
{
    /** Application services */

    /** @var Configuration Config */
    protected Configuration $config;

    /** @var ApiService API service */
    protected ApiService $api;

    /** @var ResourceService Resource service */
    protected ResourceService $resource;

    /** @var RobotService Robot service */
    protected RobotService $robot;

    /** @var Message Message */
    public Message $message;

    /** @var ChatSettings Chat settings */
    protected ChatSettings $chatSettings;

    /** Order information */

    /** @var string Matched string */
    protected string $match;

    /** @var string Order */
    protected string $order;

    /** @var bool If message sender at robot */
    protected bool $at;

    /** @var string Reply */
    public string $reply = "";

    /**
     * The constructor.
     *
     * @param Configuration $config
     * @param ApiService $api
     * @param ResourceService $resource
     * @param RobotService $robot
     * @param Message $message
     * @param string $match
     * @param string $order
     * @param bool $at
     */
    public function __construct(
        Configuration $config,
        ApiService $api,
        ResourceService $resource,
        RobotService $robot,
        Message $message,
        string $match,
        string $order,
        bool $at
    ) {
        $this->config = $config;
        $this->api = $api;
        $this->resource = $resource;
        $this->robot = $robot;
        $this->message = $message;
        $this->match = $match;
        $this->order = $order;
        $this->at = $at;

        $this->loadChatSetting();
    }

    /**
     * Load chat settings.
     */
    private function loadChatSetting(): void
    {
        if ($this->message instanceof FriendMessage) {
            $chatType = "friend";
            $chatId = $this->message->sender->id;
        } elseif ($this->message instanceof GroupMessage) {
            $chatType = "group";
            $chatId = $this->message->sender->group->id;
        } else {
            $chatType = "temp";
            $chatId = 0;
        }

        $this->chatSettings = $this->resource->getChatSettings($chatType, $chatId);
    }

    /**
     * Check if the function is active.
     *
     * @return bool Active flag
     */
    public function checkActive(): bool
    {
        // True by default
        return $this->chatSettings->getBool("active") ?? true;
    }

    /**
     * Parse the order (and match). Regular expression is recommended to use.
     *
     * @return array Parsed elements
     */
    abstract protected function parseOrder(): array;

    /******************************************************************************
     *                          Packaged common functions                         *
     ******************************************************************************/

    /**
     * Get user nickname.
     *
     * @return string User nickname
     */
    final protected function getNickname(): string
    {
        $userId = $this->message->sender->id;
        $nickname = $this->message->sender instanceof FriendSender ?
            $this->message->sender->nickname : $this->message->sender->memberName;

        return $this->chatSettings->getNickname($userId) ?? $nickname;
    }

    /**
     * Get robot nickname
     *
     * @return string Robot nickname
     *
     * @throws MiraiApiException
     */
    final protected function getRobotNickname(): string
    {
        $nickname = $this->chatSettings->getString("robotNickname");

        if (!empty($nickname)) {
            return $nickname;
        }

        if ($this->message instanceof GroupMessage) {
            return empty(
            $nickname = $this->api->getMemberName(
                $this->message->sender->group->id,
                $this->robot->getId()
            )->getString("name", "")
            ) ? $this->robot->getNickname() : $nickname;
        } else {
            return $this->robot->getNickname();
        }
    }

    /**
     * Send message to message sender.
     *
     * @param string $message Message
     */
    final protected function sendMessage(string $message): void
    {
        if ($this->message instanceof FriendMessage) {
            $this->api->sendFriendMessageAsync(
                $this->message->sender->id,
                Convertor::toMessageChain($message)
            );
        } elseif ($this->message instanceof GroupMessage) {
            $this->api->sendGroupMessageAsync(
                $this->message->sender->group->id,
                Convertor::toMessageChain($message)
            );
        } elseif ($this->message instanceof TempMessage) {
            $this->api->sendTempMessageAsync(
                $this->message->sender->id,
                $this->message->sender->group->id,
                Convertor::toMessageChain($message)
            );
        }
    }

    /**
     * Send message to friend or temp (may not the message sender).
     *
     * @param string $message Message
     * @param int|null $userId User ID
     * @param int|null $groupId Group ID
     */
    final protected function sendPrivateMessage(string $message, int $userId = null, int $groupId = null): void
    {
        $userId ??= $this->message->sender->id;
        $groupId ??= $this->message->sender->group->id ?? 0;

        if ($this->robot->hasFriend($userId)) {
            $this->api->sendFriendMessageAsync($userId, Convertor::toMessageChain($message));
        } else {
            $this->api->sendTempMessageAsync($userId, $groupId, Convertor::toMessageChain($message));
        }
    }
}
