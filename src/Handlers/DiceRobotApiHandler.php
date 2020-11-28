<?php /** @noinspection PhpUndefinedClassInspection */

declare(strict_types=1);

namespace DiceRobot\Handlers;

use DiceRobot\Data\Config;
use DiceRobot\Data\Response\{AuthorizeResponse, GetCardResponse, GetNicknameResponse, JrrpResponse, KowtowResponse,
    QueryGroupResponse, SanityCheckResponse, SubmitGroupResponse, UpdateCardResponse, UpdateRobotResponse};
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

    /**
     * Initialize DiceRobot API handler.
     *
     * @param Config $config DiceRobot config.
     */
    public function initialize(Config $config): void
    {
        $this->pool = Saber::create([
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
        ]);

        $this->logger->info("DiceRobot API handler initialized.");
    }

    /******************************************************************************
     *                               DiceRobot APIs                               *
     ******************************************************************************/

    /**
     * Update robot online info.
     *
     * @param int $robotId Robot's ID.
     *
     * @return UpdateRobotResponse The response.
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    final public function updateRobot(int $robotId): UpdateRobotResponse
    {
        $options = [
            "uri" => "/dicerobot/v2/robot/{$robotId}",
            "method" => "PATCH"
        ];

        return new UpdateRobotResponse($this->request($options));
    }

    /**
     * Update robot online info asynchronously.
     *
     * @param int $robotId Robot's ID.
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
     * Get access token.
     *
     * @param int $robotId Robot's ID.
     * @param int|null $userId User's ID if operation is about user.
     *
     * @return AuthorizeResponse The response.
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    final public function authorize(int $robotId, int $userId = null): AuthorizeResponse
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

        return new AuthorizeResponse($this->request($options));
    }

    /**
     * @param int $robotId Robot's ID.
     *
     * @return GetNicknameResponse The response.
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    final public function getNickname(int $robotId): GetNicknameResponse
    {
        $options = [
            "uri" => "/dicerobot/v2/robot/{$robotId}/nickname",
            "method" => "GET"
        ];

        return new GetNicknameResponse($this->request($options));
    }

    /**
     * Query if the group is delinquent.
     *
     * @param int $groupId Group's ID.
     * @param string $token Access token.
     *
     * @return QueryGroupResponse The response.
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    final public function queryGroup(int $groupId, string $token): QueryGroupResponse
    {
        $options = [
            "uri" => "/dicerobot/v2/group/{$groupId}",
            "method" => "GET",
            "headers" => [
                "Authorization" => "Bearer {$token}"
            ]
        ];

        return new QueryGroupResponse($this->request($options));
    }

    /**
     * Submit ID of the delinquent group to public database. These group IDs will be queried when DiceRobot is added
     * to a group.
     *
     * @param int $groupId Delinquent group's ID.
     * @param string $token Access token.
     *
     * @return SubmitGroupResponse The response.
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    final public function submitGroup(int $groupId, string $token): SubmitGroupResponse
    {
        $options = [
            "uri" => "/dicerobot/v2/group/{$groupId}",
            "method" => "PUT",
            "headers" => [
                "Authorization" => "Bearer {$token}"
            ]
        ];

        return new SubmitGroupResponse($this->request($options));
    }

    /**
     * Get character card data.
     *
     * @param int $cardId ID of the character card.
     * @param string $token Access token.
     *
     * @return GetCardResponse The response.
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    final public function getCard(int $cardId, string $token): GetCardResponse
    {
        $options = [
            "uri" => "/dicerobot/v2/card/{$cardId}",
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
     * @param int $cardId ID of the character card.
     * @param string $attribute Attribute name.
     * @param int $change Change in the attribute.
     * @param string $token Access token.
     *
     * @return UpdateCardResponse The response.
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    final public function updateCard(int $cardId, string $attribute, int $change, string $token): UpdateCardResponse
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

        return new UpdateCardResponse($this->request($options));
    }

    /**
     * Sanity check.
     *
     * @param int $cardId ID of the character card.
     * @param int $checkResult Sanity check result.
     * @param array $decreases Sanity decreases.
     * @param string $token Access token.
     *
     * @return SanityCheckResponse The response.
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    final public function sanityCheck(int $cardId, int $checkResult, array $decreases, string $token): SanityCheckResponse
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

        return new SanityCheckResponse($this->request($options));
    }

    /**
     * Get jrrp, aka today's luck.
     *
     * @param int $userId User's ID.
     *
     * @return JrrpResponse The response.
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    final public function jrrp(int $userId): JrrpResponse
    {
        $options = [
            "uri" => "/dicerobot/v2/user/{$userId}/jrrp",
            "method" => "GET"
        ];

        return new JrrpResponse($this->request($options));
    }

    /**
     * Kowtow, get piety.
     *
     * @param int $userId User's ID.
     *
     * @return KowtowResponse The response.
     *
     * @throws InternalErrorException|NetworkErrorException|UnexpectedErrorException
     */
    final public function kowtow(int $userId): KowtowResponse
    {
        $options = [
            "uri" => "/dicerobot/v2/user/{$userId}/kowtow",
            "method" => "GET"
        ];

        return new KowtowResponse($this->request($options));
    }
}
