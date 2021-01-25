<?php

declare(strict_types=1);

namespace DiceRobot\Service;

use DiceRobot\Data\{Config, MiraiResponse};
use DiceRobot\Data\Response\{CreateLogResponse, FinishLogResponse, GetTokenResponse, GetCardResponse,
    GetNicknameResponse, GetLuckResponse, GetPietyResponse, QueryGroupResponse, SanityCheckResponse,
    ReportGroupResponse, UpdateCardResponse, UpdateLogResponse, UpdateRobotResponse};
use DiceRobot\Exception\{MiraiApiException, RuntimeException};
use DiceRobot\Factory\LoggerFactory;
use DiceRobot\Handlers\{DiceRobotApiHandler, MiraiApiHandler};
use Psr\Log\LoggerInterface;

/**
 * Class ApiService
 *
 * API service.
 *
 * @package DiceRobot\Service
 *
 * @method MiraiResponse about()
 * @method MiraiResponse authSession(string $authKey)
 * @method MiraiResponse verifySession(int $robotId)
 * @method MiraiResponse releaseSession(int $robotId)
 * @method MiraiResponse sendFriendMessage(int $targetId, array $messageChain)
 * @method void sendFriendMessageAsync(int $targetId, array $messageChain)
 * @method MiraiResponse sendTempMessage(int $targetId, int $groupId, array $messageChain)
 * @method void sendTempMessageAsync(int $targetId, int $groupId, array $messageChain)
 * @method MiraiResponse sendGroupMessage(int $targetId, array $messageChain)
 * @method void sendGroupMessageAsync(int $targetId, array $messageChain)
 * @method MiraiResponse recallMessage(int $messageId)
 * @method MiraiResponse fetchMessage(int $count)
 * @method MiraiResponse fetchLatestMessage(int $count)
 * @method MiraiResponse peekMessage(int $count)
 * @method MiraiResponse peekLatestMessage(int $count)
 * @method MiraiResponse countMessage()
 * @method MiraiResponse getFriendList()
 * @method MiraiResponse getGroupList()
 * @method MiraiResponse getGroupMemberList(int $targetId)
 * @method MiraiResponse muteGroupMember(int $targetId, int $memberId, int $time)
 * @method MiraiResponse unmuteGroupMember(int $targetId, int $memberId)
 * @method MiraiResponse kickGroupMember(int $targetId, int $memberId, string $message)
 * @method MiraiResponse quitGroup(int $targetId)
 * @method MiraiResponse muteAllGroupMembers(int $targetId)
 * @method MiraiResponse unmuteAllGroupMembers(int $targetId)
 * @method MiraiResponse getGroupConfig(int $targetId)
 * @method MiraiResponse setGroupConfig(int $targetId, ?string $name = null, ?string $announcement = null, ?bool $confessTalk = null, ?bool $allowMemberInvite = null, ?bool $autoApprove = null, ?bool $anonymousChat = null)
 * @method MiraiResponse getGroupMemberInfo(int $targetId, int $memberId)
 * @method MiraiResponse setGroupMemberInfo(int $targetId, int $memberId, ?string $name = null, ?string $specialTitle = null)
 * @method MiraiResponse respondToNewFriendRequestEvent(int $eventId, int $fromId, int $groupId, int $operate, string $message)
 * @method MiraiResponse respondToMemberJoinRequestEvent(int $eventId, int $fromId, int $groupId, int $operate, string $message)
 * @method MiraiResponse respondToBotInvitedJoinGroupRequestEvent(int $eventId, int $fromId, int $groupId, int $operate, string $message)
 *
 * @method UpdateRobotResponse updateRobot(int $robotId)
 * @method void updateRobotAsync(int $robotId)
 * @method GetNicknameResponse getNickname(int $robotId)
 * @method GetTokenResponse getToken(int $robotId)
 * @method GetLuckResponse getLuck(int $userId, string $token)
 * @method GetPietyResponse getPiety(int $userId, string $token)
 * @method GetCardResponse getCard(int $userId, int $cardId, string $token)
 * @method UpdateCardResponse updateCard(int $userId, int $cardId, string $item, int $change, string $token)
 * @method SanityCheckResponse sanityCheck(int $userId, int $cardId, int $checkResult, array $decreases, string $token)
 * @method QueryGroupResponse queryGroup(int $groupId, string $token)
 * @method ReportGroupResponse reportGroup(int $groupId, string $token)
 * @method CreateLogResponse createLog(int $chatId, string $chatType, string $token)
 * @method UpdateLogResponse updateLog(string $uuid, array $message)
 * @method void updateLogAsync(string $uuid, array $message)
 * @method FinishLogResponse finishLog(int $chatId, string $chatType, string $uuid, string $token)
 */
class ApiService
{
    /** @var string[] Mirai APIs. */
    private const MIRAI_APIS = [
        "about",
        "authSession", "verifySession", "releaseSession",
        "sendFriendMessage", "sendFriendMessageAsync", "sendTempMessage", "sendTempMessageAsync", "sendGroupMessage", "sendGroupMessageAsync", "recallMessage",
        "fetchMessage", "fetchLatestMessage", "peekMessage", "peekLatestMessage", "countMessage",
        "getFriendList", "getGroupList", "getGroupMemberList",
        "muteGroupMember", "unmuteGroupMember", "kickGroupMember", "quitGroup", "muteAllGroupMembers", "unmuteAllGroupMembers", "getGroupConfig", "setGroupConfig", "getGroupMemberInfo", "setGroupMemberInfo",
        "respondToNewFriendRequestEvent", "respondToMemberJoinRequestEvent", "respondToBotInvitedJoinGroupRequestEvent"
    ];

    /** @var string[] DiceRobot APIs. */
    private const DICEROBOT_APIS = [
        "updateRobot", "updateRobotAsync", "getNickname", "getToken",
        "getLuck", "getPiety",
        "getCard", "updateCard", "sanityCheck",
        "queryGroup", "reportGroup",
        "createLog", "updateLog", "updateLogAsync", "finishLog"
    ];

    /** @var MiraiApiHandler Mirai API handler. */
    protected MiraiApiHandler $miraiHandler;

    /** @var DiceRobotApiHandler DiceRobot API handler. */
    protected DiceRobotApiHandler $diceRobotHandler;

    /** @var LoggerInterface Logger. */
    protected LoggerInterface $logger;

    /**
     * The constructor.
     *
     * @param MiraiApiHandler $miraiHandler Mirai API handler.
     * @param DiceRobotApiHandler $diceRobotHandler DiceRobot API handler.
     * @param LoggerFactory $loggerFactory Logger factory.
     */
    public function __construct(
        MiraiApiHandler $miraiHandler,
        DiceRobotApiHandler $diceRobotHandler,
        LoggerFactory $loggerFactory
    ) {
        $this->miraiHandler = $miraiHandler;
        $this->diceRobotHandler = $diceRobotHandler;

        $this->logger = $loggerFactory->create("Api");

        $this->logger->debug("API service created.");
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
        $this->logger->debug("API service destructed.");
    }

    /**
     * Call methods of the handlers.
     *
     * @param string $func Method name.
     * @param array $arguments Arguments.
     *
     * @return mixed Result of calling the method.
     *
     * @throws RuntimeException Call to undefined method.
     */
    public function __call(string $func, $arguments)
    {
        if (in_array($func, self::MIRAI_APIS)) {
            return $this->miraiHandler->$func(...$arguments);
        } elseif (in_array($func, self::DICEROBOT_APIS)) {
            return $this->diceRobotHandler->$func(...$arguments);
        }

        throw new RuntimeException("Call to undefined method '{$func}'.");
    }

    /**
     * Initialize API service.
     *
     * @param Config $config DiceRobot config.
     */
    public function initialize(Config $config): void
    {
        $this->miraiHandler->initialize($config);
        $this->diceRobotHandler->initialize($config);

        $this->logger->notice("API service initialized.");
    }

    /**
     * Initialize Mirai session.
     *
     * @param string $authKey Mirai API HTTP plugin authorization key.
     * @param int $robotId Robot ID.
     *
     * @return bool Success.
     *
     * @throws MiraiApiException
     */
    public function initSession(string $authKey, int $robotId): bool
    {
        // Create session
        $result = $this->miraiHandler->authSession($authKey);

        if (0 != $result->getInt("code", -1)) {
            $this->logger->alert("Initialize session failed, session not created.");

            return false;
        }

        $this->miraiHandler->setSession($result->getString("session"));

        $this->logger->info("Session created.");

        // Verify session
        $code = $this->miraiHandler->verifySession($robotId)->getInt("code", -1);

        if (0 != $code) {
            $this->logger->alert("Initialize session failed, session unauthorized, code {$code}.");

            return false;
        }

        $this->logger->info("Session verified.");
        $this->logger->notice("Session initialized.");

        return true;
    }
}
