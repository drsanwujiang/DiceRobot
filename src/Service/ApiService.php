<?php

declare(strict_types=1);

namespace DiceRobot\Service;

use DiceRobot\Data\Config;
use DiceRobot\Data\Response\{AuthorizeResponse, GetCardResponse, GetNicknameResponse, JrrpResponse, KowtowResponse,
    QueryGroupResponse, SanityCheckResponse, SubmitGroupResponse, UpdateCardResponse, UpdateRobotResponse};
use DiceRobot\Exception\MiraiApiException;
use DiceRobot\Exception\ApiException\{InternalErrorException, NetworkErrorException, UnexpectedErrorException};
use DiceRobot\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;
use Selective\ArrayReader\ArrayReader;
use Swlib\Http\ContentType;
use Swlib\Http\Exception\{ClientException, ServerException, TransferException};
use Swlib\Saber;

/**
 * Class ApiService
 *
 * API service.
 *
 * @package DiceRobot\Service
 */
class ApiService
{
    /** @var LoggerInterface Logger */
    protected LoggerInterface $logger;

    /** @var Saber[] Client pools */
    protected array $pools;

    /** @var string Mirai API HTTP plugin session key */
    protected string $sessionKey = "";

    /**
     * The constructor.
     *
     * @param LoggerFactory $loggerFactory
     */
    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->create("Api");
    }

    /**
     * Initialize API service.
     *
     * @param Config $config
     */
    public function initialize(Config $config): void
    {
        $this->pools = [
            Saber::create([
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
            ]),
            Saber::create([
                "base_uri" => $config->getString("dicerobot.api.prefix"),
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
            ])
        ];

        $this->logger->notice("API service initialized.");
    }

    /**
     * Test if the session key exists.
     *
     * @return bool
     */
    public function hasSession(): bool
    {
        return !empty($this->sessionKey);
    }

    /**
     * Initialize Mirai session.
     *
     * @param string $authKey
     * @param int $robotId
     *
     * @return bool
     *
     * @throws MiraiApiException
     */
    public function initSession(string $authKey, int $robotId): bool
    {
        // Create session
        $result = $this->authSession($authKey);

        if (0 != $result->getInt("code", -1)) {
            $this->logger->alert("Initialize session failed, session not created.");

            return false;
        }

        $this->sessionKey = $result->getString("session");

        $this->logger->info("Session created.");

        // Verify session
        $code = $this->verifySession($robotId)->getInt("code", -1);

        if (0 != $code) {
            $this->logger->alert("Initialize session failed, session unauthorized, code {$code}.");

            return false;
        }

        $this->logger->info("Session verified.");
        $this->logger->notice("Session initialized.");

        return true;
    }

    /**
     * Request Mirai API HTTP via pool.
     *
     * @param array $options
     *
     * @return array
     *
     * @throws MiraiApiException
     */
    protected function mRequest(array $options): array
    {
        try {
            $response = $this->pools[0]->request($options);
        } catch (TransferException $e) {  // TODO: catch (TransferException) in PHP 8
            $this->logger->alert("Request Mirai API failed.");

            throw new MiraiApiException();
        }

        return $response->getParsedJsonArray();
    }

    /**
     * Request DiceRobot API via pool.
     *
     * @param array $options
     *
     * @return array
     *
     * @throws InternalErrorException|NetworkErrorException
     */
    protected function dRequest(array $options): array
    {
        /**
         * DiceRobot API will often return 2xx Success status code, which will not throw ClientException or
         * ServerException, except when request is unauthorized (401), API does not exist (404) or server-side error
         * occurs (50x).
         */
        try {
            $options["headers"]["Timestamp"] = time();
            $response = $this->pools[1]->request($options);
        } catch (ClientException | ServerException $e) {
            $this->logger->critical(
                "DiceRobot API returned HTTP status code {$e->getResponse()->getStatusCode()}."
            );

            throw new InternalErrorException();
        } catch (TransferException $e) {  // TODO: catch (TransferException) in PHP 8
            $this->logger->critical("Request DiceRobot API failed.");

            throw new NetworkErrorException();
        }

        $data = $response->getParsedJsonArray();

        // Log error, but not throw exception
        if (0 != $data["code"]) {
            $this->logger->warning(
                "API server returned unexpected code {$data["code"]}, error message: {$data["message"]}."
            );
        }

        return $data;
    }

    /******************************************************************************
     *                                  Mirai API                                 *
     ******************************************************************************/

    /** Session */

    /**
     * @param string $authKey
     *
     * @return ArrayReader
     *
     * @throws MiraiApiException
     */
    public function authSession(string $authKey): ArrayReader
    {
        $options = [
            "uri" => "/auth",
            "method" => "POST",
            "data" => [
                "authKey" => $authKey
            ]
        ];

        return new ArrayReader($this->mRequest($options));
    }

    /**
     * @param int $qq
     *
     * @return ArrayReader
     *
     * @throws MiraiApiException
     */
    public function verifySession(int $qq): ArrayReader
    {
        $options = [
            "uri" => "/verify",
            "method" => "POST",
            "data" => [
                "sessionKey" => $this->sessionKey,
                "qq" => $qq
            ]
        ];

        return new ArrayReader($this->mRequest($options));
    }

    /** Message */

    /**
     * @param int $target
     * @param array $messageChain
     *
     * @return ArrayReader
     *
     * @throws MiraiApiException
     */
    public function sendFriendMessage(int $target, array $messageChain): ArrayReader
    {
        $options = [
            "uri" => "/sendFriendMessage",
            "method" => "POST",
            "data" => [
                "sessionKey" => $this->sessionKey,
                "target" => $target,
                "messageChain" => $messageChain
            ]
        ];

        return new ArrayReader($this->mRequest($options));
    }

    /**
     * @param int $target
     * @param array $messageChain
     */
    public function sendFriendMessageAsync(int $target, array $messageChain): void
    {
        go(function () use ($target, $messageChain) {
            try {
                $this->sendFriendMessage($target, $messageChain);
            } catch (MiraiApiException $e) {  // TODO: catch (MiraiApiException) in PHP 8
                // Do nothing
            }
        });
    }

    /**
     * @param int $qq
     * @param int $group
     * @param array $messageChain
     *
     * @return ArrayReader
     *
     * @throws MiraiApiException
     */
    public function sendTempMessage(int $qq, int $group, array $messageChain): ArrayReader
    {
        $options = [
            "uri" => "/sendTempMessage",
            "method" => "POST",
            "data" => [
                "sessionKey" => $this->sessionKey,
                "qq" => $qq,
                "group" => $group,
                "messageChain" => $messageChain
            ]
        ];

        return new ArrayReader($this->mRequest($options));
    }

    /**
     * @param int $qq
     * @param int $group
     * @param array $messageChain
     */
    public function sendTempMessageAsync(int $qq, int $group, array $messageChain): void
    {
        go(function () use ($qq, $group, $messageChain) {
            try {
                $this->sendTempMessage($qq, $group, $messageChain);
            } catch (MiraiApiException $e) {  // TODO: catch (MiraiApiException) in PHP 8
                // Do nothing
            }
        });
    }

    /**
     * @param int $target
     * @param array $messageChain
     *
     * @return ArrayReader
     *
     * @throws MiraiApiException
     */
    public function sendGroupMessage(int $target, array $messageChain): ArrayReader
    {
        $options = [
            "uri" => "/sendGroupMessage",
            "method" => "POST",
            "data" => [
                "sessionKey" => $this->sessionKey,
                "target" => $target,
                "messageChain" => $messageChain
            ]
        ];

        return new ArrayReader($this->mRequest($options));
    }

    /**
     * @param int $target
     * @param array $messageChain
     */
    public function sendGroupMessageAsync(int $target, array $messageChain): void
    {
        go(function () use ($target, $messageChain) {
            try {
                $this->sendGroupMessage($target, $messageChain);
            } catch (MiraiApiException $e) {  // TODO: catch (MiraiApiException) in PHP 8
                // Do nothing
            }
        });
    }

    /** List */

    /**
     * @return ArrayReader
     *
     * @throws MiraiApiException
     */
    public function getFriendList(): ArrayReader
    {
        $options = [
            "uri" => "/friendList?sessionKey={$this->sessionKey}",
            "method" => "GET"
        ];

        return new ArrayReader($this->mRequest($options));
    }

    /**
     * @return ArrayReader
     *
     * @throws MiraiApiException
     */
    public function getGroupList(): ArrayReader
    {
        $options = [
            "uri" => "/groupList?sessionKey={$this->sessionKey}",
            "method" => "GET"
        ];

        return new ArrayReader($this->mRequest($options));
    }

    /** Management */

    /**
     * @param int $target
     *
     * @return ArrayReader
     *
     * @throws MiraiApiException
     */
    public function quitGroup(int $target): ArrayReader
    {
        $options = [
            "uri" => "/quit",
            "method" => "POST",
            "data" => [
                "sessionKey" => $this->sessionKey,
                "target" => $target
            ]
        ];

        return new ArrayReader($this->mRequest($options));
    }

    /**
     * @param int $target
     * @param int $memberId
     *
     * @return ArrayReader
     *
     * @throws MiraiApiException
     */
    public function getMemberName(int $target, int $memberId): ArrayReader
    {
        $options = [
            "uri" => "/memberInfo?sessionKey={$this->sessionKey}&target={$target}&memberId={$memberId}",
            "method" => "GET"
        ];

        return new ArrayReader($this->mRequest($options));
    }

    /**
     * @param int $target
     * @param int $memberId
     * @param string $name
     *
     * @return ArrayReader
     *
     * @throws MiraiApiException
     */
    public function setMemberName(int $target, int $memberId, string $name): ArrayReader
    {
        $options = [
            "uri" => "/memberInfo",
            "method" => "POST",
            "data" => [
                "sessionKey" => $this->sessionKey,
                "target" => $target,
                "memberId" => $memberId,
                "info" => [
                    "name" => $name
                ]
            ]
        ];

        return new ArrayReader($this->mRequest($options));
    }

    /** Event response */

    /**
     * @param int $eventId
     * @param int $fromId
     * @param int $groupId
     * @param int $operate
     * @param string $message
     *
     * @return ArrayReader
     *
     * @throws MiraiApiException
     */
    public function respondToNewFriendRequestEvent(
        int $eventId,
        int $fromId,
        int $groupId,
        int $operate = 0,
        string $message = ""
    ): ArrayReader {
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

        return new ArrayReader($this->mRequest($options));
    }

    /**
     * @param int $eventId
     * @param int $fromId
     * @param int $groupId
     * @param int $operate
     * @param string $message
     *
     * @return ArrayReader
     *
     * @throws MiraiApiException
     */
    public function respondToBotInvitedJoinGroupRequestEvent(
        int $eventId,
        int $fromId,
        int $groupId,
        int $operate = 0,
        string $message = ""
    ): ArrayReader {
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

        return new ArrayReader($this->mRequest($options));
    }

    /**
     * Unimplemented APIs
     *
     * Following are the unimplemented APIs of Mirai API HTTP plugin, which will be implemented in the nearly future.
     */

    public function getRobotInfo() {}

    /******************************************************************************
     *                                DiceRobot API                               *
     ******************************************************************************/

    /**
     * Update robot online info.
     *
     * @param int $robotId QQ ID of the robot
     *
     * @return UpdateRobotResponse
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    public function updateRobot(int $robotId): UpdateRobotResponse
    {
        $options = [
            "uri" => "/dicerobot/v2/robot/{$robotId}",
            "method" => "PATCH"
        ];

        return new UpdateRobotResponse($this->dRequest($options));
    }

    /**
     * Update robot online info asynchronously.
     *
     * @param int $robotId QQ ID of the robot
     */
    public function updateRobotAsync(int $robotId): void
    {
        go(function () use ($robotId) {
            try {
                $this->updateRobot($robotId);
            } catch (InternalErrorException | NetworkErrorException | UnexpectedErrorException $e) {  // TODO: catch (InternalErrorException | NetworkErrorException | UnexpectedErrorException) in PHP 8
                // Do nothing
            }
        });
    }

    /**
     * Get access token.
     *
     * @param int $robotId QQ ID of the robot
     * @param int|null $userId QQ ID of message sender
     *
     * @return AuthorizeResponse
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    public function auth(int $robotId, int $userId = null): AuthorizeResponse
    {
        if ($userId) {
            $url = "/dicerobot/v2/robot/{$robotId}/auth/{$userId}";
        } else {
            $url = "/dicerobot/v2/robot/{$robotId}/auth";
        }

        $options = [
            "uri" => $url,
            "method" => "GET"
        ];

        return new AuthorizeResponse($this->dRequest($options));
    }

    /**
     * @param int $robotId QQ ID of the robot
     *
     * @return GetNicknameResponse
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    public function getNickname(int $robotId): GetNicknameResponse
    {
        $options = [
            "uri" => "/dicerobot/v2/robot/{$robotId}/nickname",
            "method" => "GET"
        ];

        return new GetNicknameResponse($this->dRequest($options));
    }

    /**
     * Query if the group is delinquent.
     *
     * @param int $groupId Group ID
     * @param string $token Access token
     *
     * @return QueryGroupResponse The response
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    public function queryGroup(int $groupId, string $token): QueryGroupResponse
    {
        $options = [
            "uri" => "/dicerobot/v2/group/{$groupId}",
            "method" => "GET",
            "headers" => [
                "Authorization" => "Bearer {$token}"
            ]
        ];

        return new QueryGroupResponse($this->dRequest($options));
    }

    /**
     * Submit ID of the delinquent group to public database. These group IDs will be queried when DiceRobot is added
     * to a group.
     *
     * @param int $groupId Delinquent group ID
     * @param string $token Access token
     *
     * @return SubmitGroupResponse The response
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    public function submitGroup(int $groupId, string $token): SubmitGroupResponse
    {
        $options = [
            "uri" => "/dicerobot/v2/group/{$groupId}",
            "method" => "PUT",
            "headers" => [
                "Authorization" => "Bearer {$token}"
            ]
        ];

        return new SubmitGroupResponse($this->dRequest($options));
    }

    /**
     * Get character card data.
     *
     * @param int $cardId Character card ID
     * @param string $token Access token
     *
     * @return GetCardResponse The response
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    public function getCard(int $cardId, string $token): GetCardResponse
    {
        $options = [
            "uri" => "/dicerobot/v2/card/{$cardId}",
            "method" => "GET",
            "headers" => [
                "Authorization" => "Bearer {$token}"
            ]
        ];

        return new GetCardResponse($this->dRequest($options));
    }

    /**
     * Update character card data.
     *
     * @param int $cardId Character card ID
     * @param string $attribute Attribute name
     * @param int $change Change in attribute
     * @param string $token Access token
     *
     * @return UpdateCardResponse The response
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    public function updateCard(int $cardId, string $attribute, int $change, string $token): UpdateCardResponse
    {
        $options = [
            "uri" => "/dicerobot/v2/card/{$cardId}",
            "method" => "PATCH",
            "headers" => [
                "Authorization" => "Bearer {$token}"
            ],
            "data" => [
                "attribute" => $attribute,
                "change" => $change
            ]
        ];

        return new UpdateCardResponse($this->dRequest($options));
    }

    /**
     * Sanity check.
     *
     * @param int $cardId Character card ID
     * @param int $checkResult Sanity check result
     * @param array $decreases Sanity decreases
     * @param string $token Access token
     *
     * @return SanityCheckResponse The response
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    public function sanityCheck(int $cardId, int $checkResult, array $decreases, string $token): SanityCheckResponse
    {
        $options = [
            "uri" => "/dicerobot/v2/card/{$cardId}/sc",
            "method" => "PATCH",
            "headers" => [
                "Authorization" => "Bearer {$token}"
            ],
            "data" => [
                "check_result" => $checkResult,
                "decreases" => $decreases
            ]
        ];

        return new SanityCheckResponse($this->dRequest($options));
    }

    /**
     * Jrrp, aka today's luck.
     *
     * @param int $userId QQ ID of message sender
     *
     * @return JrrpResponse The response
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    public function jrrp(int $userId): JrrpResponse
    {
        $options = [
            "uri" => "/dicerobot/v2/user/{$userId}/jrrp",
            "method" => "GET"
        ];

        return new JrrpResponse($this->dRequest($options));
    }

    /**
     * Kowtow, get piety.
     *
     * @param int $userId QQ ID of message sender
     *
     * @return KowtowResponse The response
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    public function kowtow(int $userId): KowtowResponse
    {
        $options = [
            "uri" => "/dicerobot/v2/user/{$userId}/kowtow",
            "method" => "GET"
        ];

        return new KowtowResponse($this->dRequest($options));
    }
}
