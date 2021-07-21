<?php

declare(strict_types=1);

namespace DiceRobot\Handlers;

use DiceRobot\Data\Config;
use DiceRobot\Data\Response\{CreateLogResponse, FinishLogResponse, GetTokenResponse, GetCardResponse,
    GetNicknameResponse, GetLuckResponse, GetPietyResponse, QueryGroupResponse, SanityCheckResponse,
    ReportGroupResponse, UpdateCardResponse, UpdateLogResponse, UpdateRobotResponse};
use DiceRobot\Exception\ApiException\{InternalErrorException, NetworkErrorException, UnexpectedErrorException};
use DiceRobot\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;
use Swlib\Http\ContentType;
use Swlib\Http\Exception\{ClientException, ServerException, TransferException};
use Swlib\Saber;

/**
 * Class DiceRobotApiHandler
 *
 * Mirai API handler.
 *
 * @package DiceRobot\Handlers
 */
class DiceRobotApiHandler
{
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

        $this->logger->debug("DiceRobot API handler created.");
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
        $this->logger->debug("DiceRobot API handler destructed.");
    }

    /**
     * Initialize DiceRobot API handler.
     *
     * @param Config $config DiceRobot config.
     */
    public function initialize(Config $config): void
    {
        $this->pool = Saber::create([
            "base_uri" => $config->getString("dicerobot.api.uri"),
            "use_pool" => true,
            "headers" => [
                "Accept" => "application/json",
                "Accept-Encoding" => "identity",
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

        $this->logger->info("DiceRobot API handler initialized.");
    }

    /**
     * Request DiceRobot API via pool.
     *
     * @param array $options Request options.
     *
     * @return array Parsed returned data.
     *
     * @throws InternalErrorException Internal error occurred in DiceRobot API .
     * @throws NetworkErrorException Request DiceRobot API failed.
     */
    protected function request(array $options): array
    {
        /**
         * DiceRobot API will often return 2xx Success status code, which will not throw ClientException or
         * ServerException, except when request is unauthorized (401), API does not exist (404) or server-side error
         * occurs (50x).
         */
        try {
            $options["headers"]["Timestamp"] = time();
            $response = $this->pool->request($options);
        } catch (ClientException | ServerException $e) {
            $this->logger->critical(
                "Failed to request DiceRobot API. HTTP status code {$e->getResponse()->getStatusCode()}."
            );

            throw new InternalErrorException();
        } catch (TransferException $e) {  // TODO: catch (TransferException) in PHP 8
            $this->logger->critical("Failed to request DiceRobot API for network problem.");

            throw new NetworkErrorException();
        }

        $data = $response->getParsedJsonArray();

        // Log error, but not throw exception
        if (0 != $data["code"]) {
            $this->logger->warning(
                "DiceRobot API returned unexpected code {$data["code"]}, error message: {$data["message"]}."
            );
        }

        return $data;
    }

    /******************************************************************************
     *                               DiceRobot APIs                               *
     ******************************************************************************/

    /** Robot */

    /**
     * Update robot online info.
     *
     * @param int $robotId Robot ID.
     *
     * @return UpdateRobotResponse Response.
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    final public function updateRobot(int $robotId): UpdateRobotResponse
    {
        $options = [
            "uri" => "robot/{$robotId}",
            "method" => "PATCH"
        ];

        return new UpdateRobotResponse($this->request($options));
    }

    /**
     * Update robot online info asynchronously.
     *
     * @param int $robotId Robot ID.
     */
    final public function updateRobotAsync(int $robotId): void
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
     * @param int $robotId Robot ID.
     *
     * @return GetNicknameResponse Response.
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    final public function getNickname(int $robotId): GetNicknameResponse
    {
        $options = [
            "uri" => "robot/{$robotId}/nickname",
            "method" => "GET"
        ];

        return new GetNicknameResponse($this->request($options));
    }

    /**
     * Get access token.
     *
     * @param int $robotId Robot ID.
     *
     * @return GetTokenResponse Response.
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    final public function getToken(int $robotId): GetTokenResponse
    {
        $options = [
            "uri" => "robot/{$robotId}/token",
            "method" => "GET"
        ];

        return new GetTokenResponse($this->request($options));
    }

    /** User */

    /**
     * Get today's luck.
     *
     * @param int $userId User ID.
     * @param string $token Access token.
     *
     * @return GetLuckResponse Response.
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    final public function getLuck(int $userId, string $token): GetLuckResponse
    {
        $options = [
            "uri" => "user/{$userId}/luck",
            "method" => "GET",
            "headers" => [
                "Authorization" => "Bearer {$token}"
            ]
        ];

        return new GetLuckResponse($this->request($options));
    }

    /**
     * Get piety.
     *
     * @param int $userId User ID.
     * @param string $token Access token.
     *
     * @return GetPietyResponse Response.
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    final public function getPiety(int $userId, string $token): GetPietyResponse
    {
        $options = [
            "uri" => "user/{$userId}/piety",
            "method" => "GET",
            "headers" => [
                "Authorization" => "Bearer {$token}"
            ]
        ];

        return new GetPietyResponse($this->request($options));
    }

    /** Card */

    /**
     * Get character card data.
     *
     * @param int $userId User ID.
     * @param int $cardId Character card ID.
     * @param string $token Access token.
     *
     * @return GetCardResponse Response.
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    final public function getCard(int $userId, int $cardId, string $token): GetCardResponse
    {
        $options = [
            "uri" => "user/{$userId}/card/{$cardId}",
            "method" => "GET",
            "headers" => [
                "Authorization" => "Bearer {$token}"
            ]
        ];

        return new GetCardResponse($this->request($options));
    }

    /**
     * Update character card data.
     *
     * @param int $userId User ID.
     * @param int $cardId Character card ID.
     * @param string $item Item name.
     * @param int $change Change to the item.
     * @param string $token Access token.
     *
     * @return UpdateCardResponse Response.
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    final public function updateCard(
        int $userId,
        int $cardId,
        string $item,
        int $change,
        string $token
    ): UpdateCardResponse {
        $options = [
            "uri" => "user/{$userId}/card/{$cardId}",
            "method" => "PATCH",
            "headers" => [
                "Authorization" => "Bearer {$token}"
            ],
            "data" => [
                "user_id" => $userId,
                "item" => $item,
                "change" => $change
            ]
        ];

        return new UpdateCardResponse($this->request($options));
    }

    /**
     * Request a sanity check to the character card.
     *
     * @param int $userId User ID.
     * @param int $cardId Character card ID.
     * @param int $checkResult Sanity check result.
     * @param array $decreases Sanity decreases.
     * @param string $token Access token.
     *
     * @return SanityCheckResponse Response.
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    final public function sanityCheck(
        int $userId,
        int $cardId,
        int $checkResult,
        array $decreases,
        string $token
    ): SanityCheckResponse {
        $options = [
            "uri" => "user/{$userId}/card/{$cardId}/sc",
            "method" => "POST",
            "headers" => [
                "Authorization" => "Bearer {$token}"
            ],
            "data" => [
                "check_result" => $checkResult,
                "decreases" => $decreases
            ]
        ];

        return new SanityCheckResponse($this->request($options));
    }

    /** Group */

    /**
     * Query if the group is delinquent.
     *
     * @param int $groupId Group ID.
     * @param string $token Access token.
     *
     * @return QueryGroupResponse Response.
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    final public function queryGroup(int $groupId, string $token): QueryGroupResponse
    {
        $options = [
            "uri" => "group/{$groupId}",
            "method" => "GET",
            "headers" => [
                "Authorization" => "Bearer {$token}"
            ]
        ];

        return new QueryGroupResponse($this->request($options));
    }

    /**
     * Report the delinquent group's ID. These group IDs will be queried when robot is added to a group.
     *
     * @param int $groupId Delinquent group ID.
     * @param string $token Access token.
     *
     * @return ReportGroupResponse Response.
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    final public function reportGroup(int $groupId, string $token): ReportGroupResponse
    {
        $options = [
            "uri" => "group/{$groupId}",
            "method" => "PUT",
            "headers" => [
                "Authorization" => "Bearer {$token}"
            ]
        ];

        return new ReportGroupResponse($this->request($options));
    }

    /** Log */

    /**
     * Create a new TRPG log.
     *
     * @param int $chatId Chat ID.
     * @param string $chatType Chat type.
     * @param string $token Access token.
     *
     * @return CreateLogResponse Response.
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    final public function createLog(int $chatId, string $chatType, string $token): CreateLogResponse
    {
        $options = [
            "uri" => "log",
            "method" => "PUT",
            "headers" => [
                "Authorization" => "Bearer {$token}"
            ],
            "data" => [
                "chat_id" => $chatId,
                "chat_type" => $chatType
            ]
        ];

        return new CreateLogResponse($this->request($options));
    }

    /**
     * Update the TRPG log.
     *
     * @param string $uuid Log UUID.
     * @param array $message Message chain.
     *
     * @return UpdateLogResponse Response.
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    final public function updateLog(string $uuid, array $message): UpdateLogResponse
    {
        $options = [
            "uri" => "log/{$uuid}",
            "method" => "PATCH",
            "data" => [
                "message" => $message,
            ]
        ];

        return new UpdateLogResponse($this->request($options));
    }

    /**
     * Update the TRPG log asynchronously.
     *
     * @param string $uuid Log UUID.
     * @param array $message Message chain.
     */
    final public function updateLogAsync(string $uuid, array $message): void
    {
        go(function () use ($uuid, $message) {
            try {
                $this->updateLog($uuid, $message);
            } catch (InternalErrorException | NetworkErrorException | UnexpectedErrorException $e) {  // TODO: catch (InternalErrorException | NetworkErrorException | UnexpectedErrorException) in PHP 8
                // Do nothing
            }
        });
    }

    /**
     * Finish the TRPG log.
     *
     * @param int $chatId Chat ID.
     * @param string $chatType Chat type.
     * @param string $uuid Log UUID.
     * @param string $token Access token.
     *
     * @return FinishLogResponse Response.
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    final public function finishLog(int $chatId, string $chatType, string $uuid, string $token): FinishLogResponse
    {
        $options = [
            "uri" => "log/{$uuid}/finish",
            "method" => "POST",
            "headers" => [
                "Authorization" => "Bearer {$token}"
            ],
            "data" => [
                "chat_id" => $chatId,
                "chat_type" => $chatType
            ]
        ];

        return new FinishLogResponse($this->request($options));
    }
}
