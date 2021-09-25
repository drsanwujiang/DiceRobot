<?php

declare(strict_types=1);

namespace DiceRobot\Service;

use DiceRobot\Data\MiraiResponse;
use DiceRobot\Data\Response\{CreateLogResponse, FinishLogResponse, GetTokenResponse, GetCardResponse,
    GetNicknameResponse, GetLuckResponse, GetPietyResponse, QueryGroupResponse, SanityCheckResponse,
    ReportMalfunctionResponse, ReportGroupResponse, UpdateCardResponse, UpdateLogResponse, UpdateRobotResponse};
use DiceRobot\Exception\RuntimeException;
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
 * @method MiraiResponse getSessionInfo()
 * @method MiraiResponse getMessageFromId(int $messageId)
 * @method MiraiResponse getFriendList()
 * @method MiraiResponse getGroupList()
 * @method MiraiResponse getGroupMemberList(int $targetId)
 * @method MiraiResponse getBotProfile()
 * @method MiraiResponse getFriendProfile(int $targetId)
 * @method MiraiResponse getMemberProfile(int $targetId, int $memberId)
 * @method MiraiResponse sendFriendMessage(int $friendId, array $messageChain, ?int $quoteId = null)
 * @method void sendFriendMessageAsync(int $friendId, array $messageChain, ?int $quoteId = null)
 * @method MiraiResponse sendGroupMessage(int $groupId, array $messageChain, ?int $quoteId = null)
 * @method void sendGroupMessageAsync(int $groupId, array $messageChain, ?int $quoteId = null)
 * @method MiraiResponse sendTempMessage(int $targetId, int $groupId, array $messageChain, ?int $quoteId = null)
 * @method void sendTempMessageAsync(int $targetId, int $groupId, array $messageChain, ?int $quoteId = null)
 * @method MiraiResponse sendNudgeMessage(int $targetId, int $subjectId, string $subjectType)
 * @method MiraiResponse recallMessage(int $messageId)
 * @method MiraiResponse getFileList(string $directoryId, int $targetId)
 * @method MiraiResponse getFileInfo(string $fileId, int $targetId)
 * @method MiraiResponse createDirectory(string $parentId, int $targetId, string $directoryName)
 * @method MiraiResponse deleteFile(string $fileId, int $targetId)
 * @method MiraiResponse moveFile(string $fileId, int $targetId, string $directoryId)
 * @method MiraiResponse renameFile(string $fileId, int $targetId, string $fileName)
 * @method MiraiResponse deleteFriend($friendId)
 * @method MiraiResponse muteMember(int $groupId, int $memberId, int $time)
 * @method MiraiResponse unmuteMember(int $groupId, int $memberId)
 * @method MiraiResponse kickMember(int $groupId, int $memberId, string $message)
 * @method MiraiResponse quitGroup(int $groupId)
 * @method MiraiResponse muteAllMembers(int $groupId)
 * @method MiraiResponse unmuteAllMembers(int $groupId)
 * @method MiraiResponse setEssenceMessage(int $messageId)
 * @method MiraiResponse getGroupConfig(int $groupId)
 * @method MiraiResponse setGroupConfig(int $groupId, ?string $name = null, ?string $announcement = null, ?bool $confessTalk = null, ?bool $allowMemberInvite = null, ?bool $autoApprove = null, ?bool $anonymousChat = null)
 * @method MiraiResponse getMemberInfo(int $groupId, int $memberId)
 * @method MiraiResponse setMemberInfo(int $groupId, int $memberId, ?string $name = null, ?string $specialTitle = null)
 * @method MiraiResponse setMemberAdmin(int $groupId, int $memberId, bool $assign)
 * @method MiraiResponse handleNewFriendRequestEvent(int $eventId, int $fromId, int $groupId, int $operate, string $message)
 * @method MiraiResponse handleMemberJoinRequestEvent(int $eventId, int $fromId, int $groupId, int $operate, string $message)
 * @method MiraiResponse handleBotInvitedJoinGroupRequestEvent(int $eventId, int $fromId, int $groupId, int $operate, string $message)
 * @method MiraiResponse countMessage()
 * @method MiraiResponse fetchMessage(int $count)
 * @method MiraiResponse fetchLatestMessage(int $count)
 * @method MiraiResponse peekMessage(int $count)
 * @method MiraiResponse peekLatestMessage(int $count)
 * @method MiraiResponse uploadImage(string $type, $file)
 * @method MiraiResponse uploadVoice(string $type, $file)
 * @method MiraiResponse uploadFile(string $type, int $targetId, string $parentId, $file)
 *
 * @method UpdateRobotResponse updateRobot(int $robotId)
 * @method void updateRobotAsync(int $robotId)
 * @method GetNicknameResponse getNickname(int $robotId)
 * @method GetTokenResponse getToken(int $robotId)
 * @method ReportMalfunctionResponse reportMalfunction(int $robotId, string $token)
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
        "about", "getSessionInfo",
        "getMessageFromId", "getFriendList", "getGroupList", "getGroupMemberList",
        "getBotProfile", "getFriendProfile", "getMemberProfile",
        "sendFriendMessage", "sendFriendMessageAsync", "sendTempMessage", "sendTempMessageAsync", "sendGroupMessage", "sendGroupMessageAsync", "sendNudgeMessage", "recallMessage",
        "getFileList", "getFileInfo", "createDirectory", "deleteFile", "moveFile", "renameFile",
        "deleteFriend",
        "muteMember", "unmuteMember", "kickMember", "quitGroup", "muteAllMembers", "unmuteAllMembers", "setEssenceMessage", "getGroupConfig", "setGroupConfig", "getMemberInfo", "setMemberInfo", "setMemberAdmin",
        "handleNewFriendRequestEvent", "handleMemberJoinRequestEvent", "handleBotInvitedJoinGroupRequestEvent",
        "countMessage", "fetchMessage", "fetchLatestMessage", "peekMessage", "peekLatestMessage",
        "uploadImage", "uploadVoice", "uploadFile"
    ];

    /** @var string[] DiceRobot APIs. */
    private const DICEROBOT_APIS = [
        "updateRobot", "updateRobotAsync", "getNickname", "getToken",
        "reportMalfunction",
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

        throw new RuntimeException("Call to undefined method \"{$func}\".");
    }

    /**
     * Initialize service.
     */
    public function initialize(): void
    {
        $this->miraiHandler->initialize();
        $this->diceRobotHandler->initialize();

        $this->logger->info("API service initialized.");
    }
}
