<?php

declare(strict_types=1);

namespace DiceRobot\Action;

use Co\System;
use DiceRobot\Data\Config;
use DiceRobot\Data\Report\Contact\Friend;
use DiceRobot\Data\Report\Message;
use DiceRobot\Data\Report\Message\{FriendMessage, GroupMessage, TempMessage};
use DiceRobot\Data\Resource\ChatSettings;
use DiceRobot\Interfaces\Action;
use DiceRobot\Service\{ApiService, ResourceService, RobotService};
use DiceRobot\Util\Convertor;

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

    /** @var Config DiceRobot config. */
    protected Config $config;

    /** @var ApiService API service. */
    protected ApiService $api;

    /** @var ResourceService Resource service. */
    protected ResourceService $resource;

    /** @var RobotService Robot service. */
    protected RobotService $robot;

    /** @var ChatSettings Chat settings. */
    protected ChatSettings $chatSettings;

    /** @var Message Message. */
    public Message $message;

    /** Order information */

    /** @var string Order match. */
    protected string $match;

    /** @var string Order. */
    protected string $order;

    /** @var bool If message sender at robot. */
    protected bool $at;

    /** @var string[] Replies. */
    public array $replies = [];

    /**
     * The constructor.
     *
     * @param Config $config DiceRobot config.
     * @param ApiService $api API service.
     * @param ResourceService $resource Resource service.
     * @param RobotService $robot Robot service.
     * @param Message $message Message.
     * @param string $match Order match.
     * @param string $order Order.
     * @param bool $at If message sender at robot.
     */
    public function __construct(
        Config $config,
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
     * @return bool Active flag.
     */
    public function checkActive(): bool
    {
        // True by default
        return $this->chatSettings->getBool("active") ?? true;
    }

    /**
     * Parse the order (and match). Regular expression is recommended to use.
     *
     * @return array Parsed elements.
     */
    abstract protected function parseOrder(): array;

    /**
     * Send replies.
     */
    public function sendReplies(): void
    {
        $maxReplyCharacter = $this->config->getOrder("maxReplyCharacter");

        // If the reply length is greater than threshold, slice it and send fragments
        while (is_string($reply = array_shift($this->replies))) {
            if (mb_strlen($reply) > $maxReplyCharacter) {
                $splitReply = mb_str_split($reply, $maxReplyCharacter);
                $reply = array_shift($splitReply);

                array_unshift($this->replies, ...$splitReply);
            }

            $this->sendMessage($reply);

            // Sleep 0.5s
            System::sleep(0.5);
        }
    }

    /******************************************************************************
     *                          Packaged common functions                         *
     ******************************************************************************/

    /**
     * Set raw reply.
     *
     * @param string $reply Raw reply.
     */
    final protected function setRawReply(string $reply): void
    {
        $this->replies[] = $reply;
    }

    /**
     * Set reply.
     *
     * @param string $replyKey Reply key.
     * @param array $variables Variables to replace with.
     */
    final protected function setReply(string $replyKey, array $variables = []): void
    {
        $this->replies[] = Convertor::toCustomString($this->config->getReply($replyKey), $variables);
    }

    /**
     * Get user nickname.
     *
     * @return string User nickname.
     */
    final protected function getNickname(): string
    {
        $userId = $this->message->sender->id;
        $nickname = $this->message->sender instanceof Friend ?
            $this->message->sender->nickname : $this->message->sender->memberName;

        return $this->chatSettings->getNickname($userId) ?? $nickname;
    }

    /**
     * Get robot nickname.
     *
     * @return string Robot nickname.
     */
    final protected function getRobotNickname(): string
    {
        $nickname = $this->chatSettings->getString("robotNickname");

        if (!empty($nickname)) {
            return $nickname;
        }

        if ($this->message instanceof GroupMessage) {
            return empty(
            $nickname = $this->api->getGroupMemberInfo(
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
     * @param string $message Message.
     */
    final public function sendMessage(string $message): void
    {
        if ($this->message instanceof FriendMessage) {
            $this->api->sendFriendMessage(
                $this->message->sender->id,
                Convertor::toMessageChain($message)
            );
        } elseif ($this->message instanceof GroupMessage) {
            $this->api->sendGroupMessage(
                $this->message->sender->group->id,
                Convertor::toMessageChain($message)
            );
        } elseif ($this->message instanceof TempMessage) {
            $this->api->sendTempMessage(
                $this->message->sender->id,
                $this->message->sender->group->id,
                Convertor::toMessageChain($message)
            );
        }
    }

    /**
     * Send message to message sender asynchronously.
     *
     * @param string $message Message.
     */
    final public function sendMessageAsync(string $message): void
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
     * @param string $message Message.
     * @param int|null $userId User ID.
     * @param int|null $groupId Group ID.
     */
    final protected function sendPrivateMessage(string $message, int $userId = null, int $groupId = null): void
    {
        $userId ??= $this->message->sender->id;
        $groupId ??= $this->message->sender->group->id ?? 0;

        if ($this->robot->hasFriend($userId)) {
            $this->api->sendFriendMessage($userId, Convertor::toMessageChain($message));
        } else {
            $this->api->sendTempMessage($userId, $groupId, Convertor::toMessageChain($message));
        }
    }

    /**
     * Send message to friend or temp (may not the message sender) asynchronously.
     *
     * @param string $message Message.
     * @param int|null $userId User ID.
     * @param int|null $groupId Group ID.
     */
    final protected function sendPrivateMessageAsync(string $message, int $userId = null, int $groupId = null): void
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
