<?php /** @noinspection PhpUndefinedClassInspection */

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
    /** @var string Mirai API HTTP plugin session key. */
    protected string $sessionKey = "";

    /** @var Saber Client pool. */
    protected Saber $pool;

    /** @var LoggerInterface Logger. */
    protected LoggerInterface $logger;

    /**
     * The constructor.
     *
     * @param LoggerFactory $loggerFactory Logger factory.
     */
    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->create("Handler");

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
            $this->logger->alert("Failed to request Mirai API for network problem.");

            throw new MiraiApiException();
        }

        return $response->getParsedJsonArray();
    }

    /**
     * Initialize Mirai API handler.
     *
     * @param Config $config DiceRobot config.
     */
    public function initialize(Config $config): void
    {
        $this->pool = Saber::create([
            "base_uri" => "http://{$config->getString("mirai.server.host")}:{$config->getString("mirai.server.port")}",
            "use_pool" => true,
            "headers" => [
                "Content-Type" => ContentType::JSON,
                "User-Agent" => "DiceRobot/{$config->getString("dicerobot.version")}"
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
     * Set Mirai API HTTP plugin session key.
     *
     * @param string $sessionKey Session key.
     */
    public function setSession(string $sessionKey): void
    {
        $this->sessionKey = $sessionKey;
    }

    /**
     * Test if the Mirai API HTTP plugin session key exists.
     *
     * @return bool Existence.
     */
    public function hasSession(): bool
    {
        return !empty($this->sessionKey);
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

    /** Session */

    /**
     * Verify identity and return a session.
     *
     * @param string $authKey Mirai API HTTP plugin authorization key.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E8%AE%A4%E8%AF%81%E4%B8%8E%E4%BC%9A%E8%AF%9D
     */
    final public function authSession(string $authKey): MiraiResponse
    {
        $options = [
            "uri" => "/auth",
            "method" => "POST",
            "data" => [
                "authKey" => $authKey
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Verify and activate the session, then bind it to a logined robot.
     *
     * @param int $robotId Robot's ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E6%A0%A1%E9%AA%8Csession
     */
    final public function verifySession(int $robotId): MiraiResponse
    {
        $options = [
            "uri" => "/verify",
            "method" => "POST",
            "data" => [
                "sessionKey" => $this->sessionKey,
                "qq" => $robotId
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Release the session and the corresponding resources.
     *
     * @param int $robotId Robot's ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E9%87%8A%E6%94%BEsession
     */
    final public function releaseSession(int $robotId): MiraiResponse
    {
        $options = [
            "uri" => "/release",
            "method" => "POST",
            "data" => [
                "sessionKey" => $this->sessionKey,
                "qq" => $robotId
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /** Message sending */

    /**
     * Send message to the specified friend.
     *
     * @param int $targetId Target friend's ID.
     * @param array $messageChain Message chain.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E5%8F%91%E9%80%81%E5%A5%BD%E5%8F%8B%E6%B6%88%E6%81%AF
     */
    final public function sendFriendMessage(int $targetId, array $messageChain): MiraiResponse
    {
        $options = [
            "uri" => "/sendFriendMessage",
            "method" => "POST",
            "data" => [
                "sessionKey" => $this->sessionKey,
                "target" => $targetId,
                "messageChain" => $messageChain
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Send message to the specified friend asynchronously.
     *
     * @param int $targetId Target friend's ID.
     * @param array $messageChain Message chain.
     *
     * @see MiraiApiHandler::sendFriendMessage()
     */
    final public function sendFriendMessageAsync(int $targetId, array $messageChain): void
    {
        go(function () use ($targetId, $messageChain) {
            try {
                $this->sendFriendMessage($targetId, $messageChain);
            } catch (MiraiApiException $e) {  // TODO: catch (MiraiApiException) in PHP 8
                // Do nothing
            }
        });
    }

    /**
     * Send message to the specified temporary chat object.
     *
     * @param int $targetId ID of the target temporary chat.
     * @param int $groupId Group's ID.
     * @param array $messageChain Message chain.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E5%8F%91%E9%80%81%E4%B8%B4%E6%97%B6%E4%BC%9A%E8%AF%9D%E6%B6%88%E6%81%AF
     */
    final public function sendTempMessage(int $targetId, int $groupId, array $messageChain): MiraiResponse
    {
        $options = [
            "uri" => "/sendTempMessage",
            "method" => "POST",
            "data" => [
                "sessionKey" => $this->sessionKey,
                "qq" => $targetId,
                "group" => $groupId,
                "messageChain" => $messageChain
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Send message to the specified temporary chat object asynchronously.
     *
     * @param int $targetId ID of the target temporary chat.
     * @param int $groupId Group's ID.
     * @param array $messageChain Message chain.
     *
     * @see MiraiApiHandler::sendTempMessage()
     */
    final public function sendTempMessageAsync(int $targetId, int $groupId, array $messageChain): void
    {
        go(function () use ($targetId, $groupId, $messageChain) {
            try {
                $this->sendTempMessage($targetId, $groupId, $messageChain);
            } catch (MiraiApiException $e) {  // TODO: catch (MiraiApiException) in PHP 8
                // Do nothing
            }
        });
    }

    /**
     * Send message to the specified group.
     *
     * @param int $targetId Target group's ID.
     * @param array $messageChain Message chain.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E5%8F%91%E9%80%81%E7%BE%A4%E6%B6%88%E6%81%AF
     */
    final public function sendGroupMessage(int $targetId, array $messageChain): MiraiResponse
    {
        $options = [
            "uri" => "/sendGroupMessage",
            "method" => "POST",
            "data" => [
                "sessionKey" => $this->sessionKey,
                "target" => $targetId,
                "messageChain" => $messageChain
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Send message to the specified group asynchronously.
     *
     * @param int $targetId Target group's ID.
     * @param array $messageChain Message chain.
     *
     * @see MiraiApiHandler::sendGroupMessage()
     */
    final public function sendGroupMessageAsync(int $targetId, array $messageChain): void
    {
        go(function () use ($targetId, $messageChain) {
            try {
                $this->sendGroupMessage($targetId, $messageChain);
            } catch (MiraiApiException $e) {  // TODO: catch (MiraiApiException) in PHP 8
                // Do nothing
            }
        });
    }

    /**
     * Recall the specific message.
     *
     * @param int $messageId ID of the target message.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E6%92%A4%E5%9B%9E%E6%B6%88%E6%81%AF
     */
    final public function recallMessage(int $messageId): MiraiResponse
    {
        $options = [
            "uri" => "/recall",
            "method" => "POST",
            "data" => [
                "sessionKey" => $this->sessionKey,
                "target" => $messageId
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /** Message receipt */

    /**
     * Get the oldest messages and events that robot received, then delete them from the message log of Mirai API HTTP
     * plugin.
     *
     * @param int $count Message/Event count.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E8%8E%B7%E5%8F%96bot%E6%94%B6%E5%88%B0%E7%9A%84%E6%B6%88%E6%81%AF%E5%92%8C%E4%BA%8B%E4%BB%B6
     */
    final public function fetchMessage(int $count): MiraiResponse
    {
        $options = [
            "uri" => "/fetchMessage?sessionKey={$this->sessionKey}&count={$count}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Get the latest messages and events that robot received, then delete them from the message log of Mirai API HTTP
     * plugin.
     *
     * @param int $count Message/Event count.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E8%8E%B7%E5%8F%96bot%E6%94%B6%E5%88%B0%E7%9A%84%E6%B6%88%E6%81%AF%E5%92%8C%E4%BA%8B%E4%BB%B6
     */
    final public function fetchLatestMessage(int $count): MiraiResponse
    {
        $options = [
            "uri" => "/fetchLatestMessage?sessionKey={$this->sessionKey}&count={$count}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Get the oldest messages and events that robot received.
     *
     * @param int $count Message/Event count.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E8%8E%B7%E5%8F%96bot%E6%94%B6%E5%88%B0%E7%9A%84%E6%B6%88%E6%81%AF%E5%92%8C%E4%BA%8B%E4%BB%B6
     */
    final public function peekMessage(int $count): MiraiResponse
    {
        $options = [
            "uri" => "/peekMessage?sessionKey={$this->sessionKey}&count={$count}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Get the latest messages and events that robot received.
     *
     * @param int $count Message/Event count.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E8%8E%B7%E5%8F%96bot%E6%94%B6%E5%88%B0%E7%9A%84%E6%B6%88%E6%81%AF%E5%92%8C%E4%BA%8B%E4%BB%B6
     */
    final public function peekLatestMessage(int $count): MiraiResponse
    {
        $options = [
            "uri" => "/peekLatestMessage?sessionKey={$this->sessionKey}&count={$count}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Count the messages and events that robot received and cached.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E6%9F%A5%E7%9C%8B%E7%BC%93%E5%AD%98%E7%9A%84%E6%B6%88%E6%81%AF%E6%80%BB%E6%95%B0
     */
    final public function countMessage(): MiraiResponse
    {
        $options = [
            "uri" => "/countMessage?sessionKey={$this->sessionKey}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /** List */

    /**
     * Get friend list of the robot.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E8%8E%B7%E5%8F%96%E5%A5%BD%E5%8F%8B%E5%88%97%E8%A1%A8
     */
    final public function getFriendList(): MiraiResponse
    {
        $options = [
            "uri" => "/friendList?sessionKey={$this->sessionKey}",
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
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E8%8E%B7%E5%8F%96%E7%BE%A4%E5%88%97%E8%A1%A8
     */
    final public function getGroupList(): MiraiResponse
    {
        $options = [
            "uri" => "/groupList?sessionKey={$this->sessionKey}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Get group member list of the robot.
     *
     * @param int $targetId Target group's ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E8%8E%B7%E5%8F%96%E7%BE%A4%E6%88%90%E5%91%98%E5%88%97%E8%A1%A8
     */
    final public function getGroupMemberList(int $targetId): MiraiResponse
    {
        $options = [
            "uri" => "/memberList?sessionKey={$this->sessionKey}&target={$targetId}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /** Management */

    /**
     * Mute the specific member in the specific group.
     *
     * @param int $targetId Target group's ID.
     * @param int $memberId Target group member's ID.
     * @param int $time Muting duration.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E7%A6%81%E8%A8%80%E7%BE%A4%E6%88%90%E5%91%98
     */
    final public function muteGroupMember(int $targetId, int $memberId, int $time): MiraiResponse
    {
        $options = [
            "uri" => "/mute",
            "method" => "POST",
            "data" => [
                "sessionKey" => $this->sessionKey,
                "target" => $targetId,
                "memberId" => $memberId,
                "time" => $time
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Unmute the specific member in the specific group.
     *
     * @param int $targetId Target group's ID.
     * @param int $memberId Target group member's ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E8%A7%A3%E9%99%A4%E7%BE%A4%E6%88%90%E5%91%98%E7%A6%81%E8%A8%80
     */
    final public function unmuteGroupMember(int $targetId, int $memberId): MiraiResponse
    {
        $options = [
            "uri" => "/unmute",
            "method" => "POST",
            "data" => [
                "sessionKey" => $this->sessionKey,
                "target" => $targetId,
                "memberId" => $memberId,
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Kick the specific member in the specific group.
     *
     * @param int $targetId Target group's ID.
     * @param int $memberId Target group member's ID.
     * @param string $message Kick message.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E7%A7%BB%E9%99%A4%E7%BE%A4%E6%88%90%E5%91%98
     */
    final public function kickGroupMember(int $targetId, int $memberId, string $message): MiraiResponse
    {
        $options = [
            "uri" => "/kick",
            "method" => "POST",
            "data" => [
                "sessionKey" => $this->sessionKey,
                "target" => $targetId,
                "memberId" => $memberId,
                "msg" => $message
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Quit the group.
     *
     * @param int $targetId Target group's ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E9%80%80%E5%87%BA%E7%BE%A4%E8%81%8A
     */
    final public function quitGroup(int $targetId): MiraiResponse
    {
        $options = [
            "uri" => "/quit",
            "method" => "POST",
            "data" => [
                "sessionKey" => $this->sessionKey,
                "target" => $targetId
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Mute all members in the specific group.
     *
     * @param int $targetId Target group's ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E5%85%A8%E4%BD%93%E7%A6%81%E8%A8%80
     */
    final public function muteAllGroupMembers(int $targetId): MiraiResponse
    {
        $options = [
            "uri" => "/muteAll",
            "method" => "POST",
            "data" => [
                "sessionKey" => $this->sessionKey,
                "target" => $targetId
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Unmute all members in the specific group.
     *
     * @param int $targetId Target group's ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E8%A7%A3%E9%99%A4%E5%85%A8%E4%BD%93%E7%A6%81%E8%A8%80
     */
    final public function unmuteAllGroupMembers(int $targetId): MiraiResponse
    {
        $options = [
            "uri" => "/unmuteAll",
            "method" => "POST",
            "data" => [
                "sessionKey" => $this->sessionKey,
                "target" => $targetId
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Get group's config.
     *
     * @param int $targetId Target group's ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E8%8E%B7%E5%8F%96%E7%BE%A4%E8%AE%BE%E7%BD%AE
     */
    final public function getGroupConfig(int $targetId): MiraiResponse
    {
        $options = [
            "uri" => "/groupConfig?sessionKey={$this->sessionKey}&target={$targetId}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Set group's config.
     *
     * @param int $targetId Target group's ID.
     * @param string|null $name Target group's nickname.
     * @param string|null $announcement Target group's announcement.
     * @param bool|null $confessTalk Enable confess talk.
     * @param bool|null $allowMemberInvite Allow member invitation.
     * @param bool|null $autoApprove Automatically approve joining request.
     * @param bool|null $anonymousChat Enable anonymous chat.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E4%BF%AE%E6%94%B9%E7%BE%A4%E8%AE%BE%E7%BD%AE
     */
    final public function setGroupConfig(
        int $targetId,
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
                "sessionKey" => $this->sessionKey,
                "target" => $targetId,
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
     * Get group member's information.
     *
     * @param int $targetId Target group's ID.
     * @param int $memberId Target group member's ID.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E8%8E%B7%E5%8F%96%E7%BE%A4%E5%91%98%E8%B5%84%E6%96%99
     */
    final public function getGroupMemberInfo(int $targetId, int $memberId): MiraiResponse
    {
        $options = [
            "uri" => "/memberInfo?sessionKey={$this->sessionKey}&target={$targetId}&memberId={$memberId}",
            "method" => "GET"
        ];

        return new MiraiResponse($this->request($options));
    }

    /**
     * Set group member's information.
     *
     * @param int $targetId Target group's ID.
     * @param int $memberId Target group member's ID.
     * @param string|null $name Target group member's nickname.
     * @param string|null $specialTitle Target group member's special title.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/API.md#%E4%BF%AE%E6%94%B9%E7%BE%A4%E5%91%98%E8%B5%84%E6%96%99
     */
    final public function setGroupMemberInfo(
        int $targetId,
        int $memberId,
        ?string $name = null,
        ?string $specialTitle = null
    ): MiraiResponse {
        $options = [
            "uri" => "/memberInfo",
            "method" => "POST",
            "data" => [
                "sessionKey" => $this->sessionKey,
                "target" => $targetId,
                "memberId" => $memberId,
                "info" => [
                    "name" => $name,
                    "specialTitle" => $specialTitle
                ]
            ]
        ];

        return new MiraiResponse($this->request($options));
    }

    /** Event response */

    /**
     * Respond to NewFriendRequestEvent.
     *
     * @param int $eventId ID of the event.
     * @param int $fromId ID of the requester.
     * @param int $groupId Group's ID if request via a group.
     * @param int $operate Response operation type.
     * @param string $message Reply.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#%E6%B7%BB%E5%8A%A0%E5%A5%BD%E5%8F%8B%E7%94%B3%E8%AF%B7
     */
    final public function respondToNewFriendRequestEvent(
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
                "sessionKey" => $this->sessionKey,
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
     * Respond to MemberJoinRequestEvent.
     *
     * @param int $eventId ID of the event.
     * @param int $fromId ID of the requester.
     * @param int $groupId Requested group's ID.
     * @param int $operate Response operation type.
     * @param string $message Reply.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#%E7%94%A8%E6%88%B7%E5%85%A5%E7%BE%A4%E7%94%B3%E8%AF%B7bot%E9%9C%80%E8%A6%81%E6%9C%89%E7%AE%A1%E7%90%86%E5%91%98%E6%9D%83%E9%99%90
     */
    final public function respondToMemberJoinRequestEvent(
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
                "sessionKey" => $this->sessionKey,
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
     * Respond to BotInvitedJoinGroupRequestEvent.
     *
     * @param int $eventId ID of the event.
     * @param int $fromId ID of the requester.
     * @param int $groupId Requested group's ID.
     * @param int $operate Response operation type.
     * @param string $message Reply.
     *
     * @return MiraiResponse Response.
     *
     * @throws MiraiApiException
     *
     * @link https://github.com/project-mirai/mirai-api-http/blob/master/docs/EventType.md#bot%E8%A2%AB%E9%82%80%E8%AF%B7%E5%85%A5%E7%BE%A4%E7%94%B3%E8%AF%B7
     */
    final public function respondToBotInvitedJoinGroupRequestEvent(
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
                "sessionKey" => $this->sessionKey,
                "eventId" => $eventId,
                "fromId" => $fromId,
                "groupId" => $groupId,
                "operate" => $operate,
                "message" => $message
            ]
        ];

        return new MiraiResponse($this->request($options));
    }
}
