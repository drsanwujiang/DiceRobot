<?php

declare(strict_types=1);

namespace DiceRobot\Handlers;

use DiceRobot\Data\{Config, MiraiResponse};
use DiceRobot\Exception\MiraiApiException;
use DiceRobot\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;
use Swlib\Http\ContentType;
use Swlib\Http\Exception\TransferException;
use Swlib\Saber;

/**
 * Class MiraiApiHandler
 *
 * Mirai API handler.
 *
 * @package DiceRobot\Handlers
 */
class MiraiApiHandler
{
    /** @var Config DiceRobot config. */
    protected Config $config;

    /** @var Saber Client pool. */
    protected Saber $pool;

    /** @var LoggerInterface Logger. */
    protected LoggerInterface $logger;

    /**
     * The constructor.
     *
     * @param Config $config DiceRobot config.
     * @param LoggerFactory $loggerFactory Logger factory.
     */
    public function __construct(Config $config, LoggerFactory $loggerFactory)
    {
        $this->config = $config;

        $this->logger = $loggerFactory->create("MiraiAPI");

        $this->logger->debug("Mirai API handler created.");
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
        $this->logger->debug("Mirai API handler destructed.");
    }

    /**
     * Initialize handler.
     */
    public function initialize(): void
    {
        $this->pool = Saber::create([
            "base_uri" =>
                "http://{$this->config->getString("mirai.server.host")}:{$this->config->getString("mirai.server.port")}",
            "use_pool" => true,
            "headers" => [
                "Content-Type" => ContentType::JSON,
                "User-Agent" => "DiceRobot/{$this->config->getString("dicerobot.version")}"
            ],
            "before" => function (Saber\Request $request) {
                $this->logger->debug("Send to {$request->getUri()}, content: {$request->getBody()}");
            },
            "after" => function (Saber\Response $response) {
                $this->logger->debug("Receive from {$response->getUri()}, content: {$response->getBody()}");
            }
        ]);

        $this->logger->info("Mirai API handler initialized.");
    }

    /**
     * Request Mirai API via pool.
     *
     * @param array $options Request options.
     *
     * @return array Parsed returned data.
     *
     * @throws MiraiApiException Request Mirai API failed.
     */
    protected function request(array $options): array
    {
        try {
            $response = $this->pool->request($options);
        } catch (TransferException $e) {  // TODO: catch (TransferException) in PHP 8
            $this->logger->critical("Failed to request Mirai API for network problem.");

            throw new MiraiApiException();
        }

        return $response->getParsedJsonArray();
    }

    /******************************************************************************
     *                                 Mirai APIs                                 *
     ******************************************************************************/

    /** Plugin */

    /**
     * Get about information of Mirai API HTTP plugin.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function about(): MiraiResponse
    {
        $options = [
            "uri" => "/about",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Get session information of Mirai API HTTP plugin.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function getSessionInfo(): MiraiResponse
    {
        $options = [
            "uri" => "/sessionInfo",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /** Cache */

    /**
     * Get message by ID.
     *
     * @param int $messageId Message ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function getMessageFromId(int $messageId): MiraiResponse
    {
        $options = [
            "uri" => "/messageFromId?id={$messageId}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /** Account */

    /**
     * Get friend list of the robot.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function getFriendList(): MiraiResponse
    {
        $options = [
            "uri" => "/friendList",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Get group list of the robot.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function getGroupList(): MiraiResponse
    {
        $options = [
            "uri" => "/groupList",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Get group member list of the robot.
     *
     * @param int $groupId Target group ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function getGroupMemberList(int $groupId): MiraiResponse
    {
        $options = [
            "uri" => "/memberList?target={$groupId}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Get profile of the robot.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function getBotProfile(): MiraiResponse
    {
        $options = [
            "uri" => "/botProfile",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Get profile of the specific friend.
     *
     * @param int $friendId Target friend ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function getFriendProfile(int $friendId): MiraiResponse
    {
        $options = [
            "uri" => "/friendProfile?target={$friendId}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Get profile of the specific group member.
     *
     * @param int $groupId Target group ID.
     * @param int $memberId Target group member ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function getMemberProfile(int $groupId, int $memberId): MiraiResponse
    {
        $options = [
            "uri" => "/memberProfile?target={$groupId}&memberId={$memberId}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /** Message */

    /**
     * Send message to the specified friend.
     *
     * @param int $friendId Target friend ID.
     * @param array $messageChain Message chain.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function sendFriendMessage(
        int $friendId,
        array $messageChain,
        ?int $quoteId = null
    ): MiraiResponse {
        $options = [
            "uri" => "/sendFriendMessage",
            "method" => "POST",
            "data" => [
                "target" => $friendId,
                "quote" => $quoteId,
                "messageChain" => $messageChain
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Send message to the specified friend asynchronously.
     *
     * @param int $friendId Target friend ID.
     * @param array $messageChain Message chain.
     *
     * @see MiraiApiHandler::sendFriendMessage()
     */
    final public function sendFriendMessageAsync(
        int $friendId,
        array $messageChain,
        ?int $quoteId = null
    ): void {
        go(function () use ($friendId, $messageChain, $quoteId) {
            try {
                $this->sendFriendMessage($friendId, $messageChain, $quoteId);
            } catch (MiraiApiException $e) {  // TODO: catch (MiraiApiException) in PHP 8
                // Do nothing
            }
        });
    }

    /**
     * Send message to the specified group.
     *
     * @param int $groupId Target group ID.
     * @param array $messageChain Message chain.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function sendGroupMessage(
        int $groupId,
        array $messageChain,
        ?int $quoteId = null
    ): MiraiResponse {
        $options = [
            "uri" => "/sendGroupMessage",
            "method" => "POST",
            "data" => [
                "target" => $groupId,
                "quote" => $quoteId,
                "messageChain" => $messageChain
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Send message to the specified group asynchronously.
     *
     * @param int $groupId Target group ID.
     * @param array $messageChain Message chain.
     *
     * @see MiraiApiHandler::sendGroupMessage()
     */
    final public function sendGroupMessageAsync(
        int $groupId,
        array $messageChain,
        ?int $quoteId = null
    ): void {
        go(function () use ($groupId, $messageChain, $quoteId) {
            try {
                $this->sendGroupMessage($groupId, $messageChain, $quoteId);
            } catch (MiraiApiException $e) {  // TODO: catch (MiraiApiException) in PHP 8
                // Do nothing
            }
        });
    }

    /**
     * Send message to the specified temporary chat object.
     *
     * @param int $targetId Target temporary chat ID.
     * @param int $groupId Group ID.
     * @param array $messageChain Message chain.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function sendTempMessage(
        int $targetId,
        int $groupId,
        array $messageChain,
        ?int $quoteId = null
    ): MiraiResponse {
        $options = [
            "uri" => "/sendTempMessage",
            "method" => "POST",
            "data" => [
                "qq" => $targetId,
                "group" => $groupId,
                "quote" => $quoteId,
                "messageChain" => $messageChain
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Send message to the specified temporary chat object asynchronously.
     *
     * @param int $targetId Target temporary chat ID.
     * @param int $groupId Group ID.
     * @param array $messageChain Message chain.
     *
     * @see MiraiApiHandler::sendTempMessage()
     */
    final public function sendTempMessageAsync(
        int $targetId,
        int $groupId,
        array $messageChain,
        ?int $quoteId = null
    ): void {
        go(function () use ($targetId, $groupId, $messageChain, $quoteId) {
            try {
                $this->sendTempMessage($targetId, $groupId, $messageChain, $quoteId);
            } catch (MiraiApiException $e) {  // TODO: catch (MiraiApiException) in PHP 8
                // Do nothing
            }
        });
    }

    /**
     * Send nudge message to the specified subject.
     *
     * @param int $targetId Nudge target ID.
     * @param int $subjectId Nudge subject ID.
     * @param string $subjectType Subject type.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function sendNudgeMessage(
        int $targetId,
        int $subjectId,
        string $subjectType
    ): MiraiResponse {
        $options = [
            "uri" => "/sendNudge",
            "method" => "POST",
            "data" => [
                "target" => $targetId,
                "subject" => $subjectId,
                "kind" => $subjectType
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Recall the specific message.
     *
     * @param int $messageId Target message ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function recallMessage(int $messageId): MiraiResponse
    {
        $options = [
            "uri" => "/recall",
            "method" => "POST",
            "data" => [
                "target" => $messageId
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /** File */

    /**
     * Get file list in the specific directory of the specific target.
     *
     * @param string $directoryId Target directory ID.
     * @param int $targetId Target ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function getFileList(string $directoryId, int $targetId): MiraiResponse
    {
        $options = [
            "uri" => "/file/list?id={$directoryId}&target={$targetId}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Get the specific file information of the specific target.
     *
     * @param string $fileId Target file ID.
     * @param int $targetId Target ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function getFileInfo(string $fileId, int $targetId): MiraiResponse
    {
        $options = [
            "uri" => "/file/info?id={$fileId}&target={$targetId}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Create new directory.
     *
     * @param string $parentId Parent directory ID.
     * @param int $targetId Target ID.
     * @param string $directoryName New directory name.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function createDirectory(string $parentId, int $targetId, string $directoryName): MiraiResponse
    {
        $options = [
            "uri" => "/file/mkdir",
            "method" => "POST",
            "data" => [
                "id" => $parentId,
                "target" => $targetId,
                "directoryName" => $directoryName
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Delete the specific file of the specific target.
     *
     * @param string $fileId Target file ID.
     * @param int $targetId Target ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function deleteFile(string $fileId, int $targetId): MiraiResponse
    {
        $options = [
            "uri" => "/file/delete",
            "method" => "POST",
            "data" => [
                "id" => $fileId,
                "target" => $targetId
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Move the specific file of the specific target.
     *
     * @param string $fileId Target file ID.
     * @param int $targetId Target ID.
     * @param string $directoryId Target directory ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function moveFile(string $fileId, int $targetId, string $directoryId): MiraiResponse
    {
        $options = [
            "uri" => "/file/move",
            "method" => "POST",
            "data" => [
                "id" => $fileId,
                "target" => $targetId,
                "moveTo" => $directoryId
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Rename the specific file of the specific target.
     *
     * @param string $fileId Target file ID.
     * @param int $targetId Target ID.
     * @param string $fileName New file name.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function renameFile(string $fileId, int $targetId, string $fileName): MiraiResponse
    {
        $options = [
            "uri" => "/file/rename",
            "method" => "POST",
            "data" => [
                "id" => $fileId,
                "target" => $targetId,
                "renameTo" => $fileName
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /** Account Management */

    /**
     * Delete the specific friend.
     *
     * @param int $friendId Target friend ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function deleteFriend(int $friendId): MiraiResponse
    {
        $options = [
            "uri" => "/deleteFriend",
            "method" => "POST",
            "data" => [
                "target" => $friendId
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /** Group Management */

    /**
     * Mute the specific member in the specific group.
     *
     * @param int $groupId Target group ID.
     * @param int $memberId Target group member ID.
     * @param int $time Muting duration.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function muteMember(int $groupId, int $memberId, int $time): MiraiResponse
    {
        $options = [
            "uri" => "/mute",
            "method" => "POST",
            "data" => [
                "target" => $groupId,
                "memberId" => $memberId,
                "time" => $time
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Unmute the specific member in the specific group.
     *
     * @param int $groupId Target group ID.
     * @param int $memberId Target group member ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function unmuteMember(int $groupId, int $memberId): MiraiResponse
    {
        $options = [
            "uri" => "/unmute",
            "method" => "POST",
            "data" => [
                "target" => $groupId,
                "memberId" => $memberId,
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Kick the specific member in the specific group.
     *
     * @param int $groupId Target group ID.
     * @param int $memberId Target group member ID.
     * @param string $message Kick message.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function kickMember(int $groupId, int $memberId, string $message): MiraiResponse
    {
        $options = [
            "uri" => "/kick",
            "method" => "POST",
            "data" => [
                "target" => $groupId,
                "memberId" => $memberId,
                "msg" => $message
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Quit the specific group.
     *
     * @param int $groupId Target group ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function quitGroup(int $groupId): MiraiResponse
    {
        $options = [
            "uri" => "/quit",
            "method" => "POST",
            "data" => [
                "target" => $groupId
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Mute all members in the specific group.
     *
     * @param int $groupId Target group ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E5%85%A8%E4%BD%93%E7%A6%81%E8%A8%80
     */
    final public function muteAllMembers(int $groupId): MiraiResponse
    {
        $options = [
            "uri" => "/muteAll",
            "method" => "POST",
            "data" => [
                "target" => $groupId
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Unmute all members in the specific group.
     *
     * @param int $groupId Target group ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E8%A7%A3%E9%99%A4%E5%85%A8%E4%BD%93%E7%A6%81%E8%A8%80
     */
    final public function unmuteAllMembers(int $groupId): MiraiResponse
    {
        $options = [
            "uri" => "/unmuteAll",
            "method" => "POST",
            "data" => [
                "target" => $groupId
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Set essence message of the specific group.
     *
     * @param int $messageId Target essence message ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function setEssenceMessage(int $messageId): MiraiResponse
    {
        $options = [
            "uri" => "/setEssence",
            "method" => "POST",
            "data" => [
                "target" => $messageId
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Get config of the specific group.
     *
     * @param int $groupId Target group ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function getGroupConfig(int $groupId): MiraiResponse
    {
        $options = [
            "uri" => "/groupConfig?target={$groupId}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Set config of the specific group.
     *
     * @param int $groupId Target group ID.
     * @param string|null $name Target group nickname.
     * @param string|null $announcement Target group announcement.
     * @param bool|null $confessTalk Enable confess talk.
     * @param bool|null $allowMemberInvite Allow member invitation.
     * @param bool|null $autoApprove Automatically approve joining request.
     * @param bool|null $anonymousChat Enable anonymous chat.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function setGroupConfig(
        int $groupId,
        ?string $name = null,
        ?string $announcement = null,
        ?bool $confessTalk = null,
        ?bool $allowMemberInvite = null,
        ?bool $autoApprove = null,
        ?bool $anonymousChat = null
    ): MiraiResponse {
        $options = [
            "uri" => "/groupConfig",
            "method" => "POST",
            "data" => [
                "target" => $groupId,
                "config" => [
                    "name" => $name,
                    "announcement" => $announcement,
                    "confessTalk" => $confessTalk,
                    "allowMemberInvite" => $allowMemberInvite,
                    "autoApprove" => $autoApprove,
                    "anonymousChat" => $anonymousChat
                ]
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Get information of the specific group member.
     *
     * @param int $groupId Target group ID.
     * @param int $memberId Target group member ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function getMemberInfo(int $groupId, int $memberId): MiraiResponse
    {
        $options = [
            "uri" => "/memberInfo?target={$groupId}&memberId={$memberId}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Set information of the specific group member.
     *
     * @param int $groupId Target group ID.
     * @param int $memberId Target group member ID.
     * @param string|null $name Target group member nickname.
     * @param string|null $specialTitle Target group member special title.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function setMemberInfo(
        int $groupId,
        int $memberId,
        ?string $name = null,
        ?string $specialTitle = null
    ): MiraiResponse {
        $options = [
            "uri" => "/memberInfo",
            "method" => "POST",
            "data" => [
                "target" => $groupId,
                "memberId" => $memberId,
                "info" => [
                    "name" => $name,
                    "specialTitle" => $specialTitle
                ]
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /** Event */

    /**
     * Handle NewFriendRequestEvent.
     *
     * @param int $eventId Event ID.
     * @param int $fromId Sender ID.
     * @param int $groupId Group ID if request via a group.
     * @param int $operate Response operation type.
     * @param string $message Reply.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function handleNewFriendRequestEvent(
        int $eventId,
        int $fromId,
        int $groupId,
        int $operate,
        string $message
    ): MiraiResponse {
        $options = [
            "uri" => "/resp/newFriendRequestEvent",
            "method" => "POST",
            "data" => [
                "eventId" => $eventId,
                "fromId" => $fromId,
                "groupId" => $groupId,
                "operate" => $operate,
                "message" => $message
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Handle MemberJoinRequestEvent.
     *
     * @param int $eventId Event ID.
     * @param int $fromId Sender ID.
     * @param int $groupId Target group ID.
     * @param int $operate Response operation type.
     * @param string $message Reply.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function handleMemberJoinRequestEvent(
        int $eventId,
        int $fromId,
        int $groupId,
        int $operate,
        string $message
    ): MiraiResponse {
        $options = [
            "uri" => "/resp/memberJoinRequestEvent",
            "method" => "POST",
            "data" => [
                "eventId" => $eventId,
                "fromId" => $fromId,
                "groupId" => $groupId,
                "operate" => $operate,
                "message" => $message
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Handle BotInvitedJoinGroupRequestEvent.
     *
     * @param int $eventId Event ID.
     * @param int $fromId Sender ID.
     * @param int $groupId Target group ID.
     * @param int $operate Response operation type.
     * @param string $message Reply.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function handleBotInvitedJoinGroupRequestEvent(
        int $eventId,
        int $fromId,
        int $groupId,
        int $operate,
        string $message
    ): MiraiResponse {
        $options = [
            "uri" => "/resp/botInvitedJoinGroupRequestEvent",
            "method" => "POST",
            "data" => [
                "eventId" => $eventId,
                "fromId" => $fromId,
                "groupId" => $groupId,
                "operate" => $operate,
                "message" => $message
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /** Message Queue */

    /**
     * Count messages and events that robot received and cached.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function countMessage(): MiraiResponse
    {
        $options = [
            "uri" => "/countMessage",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Get oldest messages and events that robot received, then delete them from the message log of Mirai API HTTP
     * plugin.
     *
     * @param int $count Message/Event count.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function fetchMessage(int $count): MiraiResponse
    {
        $options = [
            "uri" => "/fetchMessage?count={$count}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Get latest messages and events that robot received, then delete them from the message log of Mirai API HTTP
     * plugin.
     *
     * @param int $count Message/Event count.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function fetchLatestMessage(int $count): MiraiResponse
    {
        $options = [
            "uri" => "/fetchLatestMessage?count={$count}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Get oldest messages and events that robot received.
     *
     * @param int $count Message/Event count.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function peekMessage(int $count): MiraiResponse
    {
        $options = [
            "uri" => "/peekMessage?count={$count}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Get latest messages and events that robot received.
     *
     * @param int $count Message/Event count.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function peekLatestMessage(int $count): MiraiResponse
    {
        $options = [
            "uri" => "/peekLatestMessage?count={$count}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /** Multimedia */

    /**
     * Upload image.
     *
     * @param string $type Target chat type.
     * @param mixed $file Image file.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function uploadImage(string $type, $file): MiraiResponse
    {
        $options = [
            "uri" => "/uploadImage",
            "method" => "POST",
            "headers" => [
                "Content-Type" => ContentType::MULTIPART
            ],
            "data" => [
                "type" => $type,
                "img" => $file
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Upload voice.
     *
     * @param string $type Target chat type.
     * @param mixed $file Voice file.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function uploadVoice(string $type, $file): MiraiResponse
    {
        $options = [
            "uri" => "/uploadVoice",
            "method" => "POST",
            "headers" => [
                "Content-Type" => ContentType::MULTIPART
            ],
            "data" => [
                "type" => $type,
                "voice" => $file
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Upload file.
     *
     * @param string $type Target chat type.
     * @param string $parentId Target directory ID.
     * @param mixed $file File.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     */
    final public function uploadFile(string $type, string $parentId, $file): MiraiResponse
    {
        $options = [
            "uri" => "/file/upload",
            "method" => "POST",
            "headers" => [
                "Content-Type" => ContentType::MULTIPART
            ],
            "data" => [
                "type" => $type,
                "path" => $parentId,
                "file" => $file
            ]
        ];

        return new MiraiResponse($this->request($options));
    }
}
