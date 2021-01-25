<?php

declare(strict_types=1);

namespace DiceRobot\Action;

use Co\System;
use DiceRobot\Data\Config;
use DiceRobot\Data\Report\Contact\Friend;
use DiceRobot\Data\Report\Message;
use DiceRobot\Util\MessageSplitter;
use DiceRobot\Data\Report\Message\{FriendMessage, GroupMessage};
use DiceRobot\Data\Resource\ChatSettings;
use DiceRobot\Factory\LoggerFactory;
use DiceRobot\Interfaces\Action;
use DiceRobot\Service\{ApiService, ResourceService, RobotService};
use DiceRobot\Util\Convertor;
use Psr\Log\LoggerInterface;

/**
 * Class MessageAction
 *
 * Action that responds to message report.
 *
 * @package DiceRobot\Action
 */
abstract class MessageAction implements Action
{
    /** Application services */

    /** @var Config DiceRobot config. */
    protected Config $config;

    /** @var MessageSplitter Message splitter. */
    private MessageSplitter $splitter;

    /** @var ApiService API service. */
    protected ApiService $api;

    /** @var ResourceService Resource service. */
    protected ResourceService $resource;

    /** @var RobotService Robot service. */
    protected RobotService $robot;

    /** @var LoggerInterface Logger. */
    protected LoggerInterface $logger;

    /** Order information */

    /** @var Message Message. */
    protected Message $message;

    /** @var ChatSettings Chat settings. */
    protected ChatSettings $chatSettings;

    /** @var string Order match. */
    protected string $match;

    /** @var string Order. */
    protected string $order;

    /** @var bool If message sender at robot. */
    protected bool $at;

    /** @var string[] Replies. */
    protected array $replies = [];

    /**
     * The constructor.
     *
     * @param Config $config DiceRobot config.
     * @param MessageSplitter $splitter Message splitter.
     * @param ApiService $api API service.
     * @param ResourceService $resource Resource service.
     * @param RobotService $robot Robot service.
     * @param LoggerFactory $loggerFactory Logger factory.
     * @param Message $message Message.
     * @param string $match Order match.
     * @param string $order Order.
     * @param bool $at If message sender at robot.
     */
    public function __construct(
        Config $config,
        MessageSplitter $splitter,
        ApiService $api,
        ResourceService $resource,
        RobotService $robot,
        LoggerFactory $loggerFactory,
        Message $message,
        string $match,
        string $order,
        bool $at
    ) {
        $this->config = $config;
        $this->splitter = $splitter;
        $this->api = $api;
        $this->resource = $resource;
        $this->robot = $robot;

        $this->logger = $loggerFactory->create("Message");

        $this->message = $message;
        $this->match = $match;
        $this->order = $order;
        $this->at = $at;

        $this->logger->debug("Message action " . static::class . " created.");

        $this->loadChatSetting();
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
        $this->logger->debug("Message action " . static::class . " destructed.");
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

        $this->logger->info("Chat settings loaded.");
    }

    /**
     * Get message of the action.
     *
     * @return Message Message.
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * Get replies of the action.
     *
     * @return string[] Replies.
     */
    public function getReplies(): array
    {
        return $this->replies;
    }

    /**
     * Check if the function is active.
     *
     * @return bool Active flag.
     */
    public function checkActive(): bool
    {
        return $this->chatSettings->getBool("active");
    }

    /**
     * Check whether the function is enabled.
     *
     * @return bool Enabled.
     */
    protected function checkEnabled(): bool
    {
        return true;
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
    final public function sendReplies(): void
    {
        $maxCharacter = static::class == GroupMessage::class ?
            $this->config->getOrder("maxReplyCharacter"):
            $this->config->getOrder("maxPrivateReplyCharacter");
        $splitReplies = $this->splitter->split($this->replies, $maxCharacter);

        foreach ($splitReplies as $reply) {
            $this->sendMessage($reply);

            // Sleep 0.5s
            System::sleep(0.5);
        }

        $this->logger->info("Replies sent.");
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
    final public function getNickname(): string
    {
        $userId = $this->message->sender->id;
        $nickname = $this->message->sender instanceof Friend ?
            $this->message->sender->nickname : $this->message->sender->memberName;

        return $this->chatSettings->getNickname($userId) ?? $nickname;
    }

    /**
     * Request to get robot nickname.
     *
     * @return string Robot nickname.
     */
    final public function getRobotNickname(): string
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
        if ($this->message instanceof GroupMessage) {
            $this->api->sendGroupMessage(
                $this->message->sender->group->id,
                Convertor::toMessageChain($message)
            );
        } else {
            $this->sendPrivateMessage(
                $message,
                $this->message->sender->id,
                $this->message->sender->group->id ?? 0
            );
        }

        $this->logger->info("Message sent.");
    }

    /**
     * Send message to message sender asynchronously.
     *
     * @param string $message Message.
     */
    final public function sendMessageAsync(string $message): void
    {
        if ($this->message instanceof GroupMessage) {
            $this->api->sendGroupMessageAsync(
                $this->message->sender->group->id,
                Convertor::toMessageChain($message)
            );
        } else {
            $this->sendPrivateMessageAsync(
                $message,
                $this->message->sender->id,
                $this->message->sender->group->id ?? 0
            );
        }

        $this->logger->info("Message sent asynchronously.");
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

        $this->logger->info("Private message sent.");
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

        $this->logger->info("Private message sent asynchronously.");
    }
}
