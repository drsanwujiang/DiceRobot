<?php
namespace DiceRobot\Service\API;

use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Exception\InformativeException\APIException\UnexpectedErrorException;
use DiceRobot\Exception\InformativeException\JSONDecodeException;
use DiceRobot\Service\API\Response\AuthorizeResponse;
use DiceRobot\Service\API\Response\GetCardResponse;
use DiceRobot\Service\API\Response\QueryGroupResponse;
use DiceRobot\Service\API\Response\SanityCheckResponse;
use DiceRobot\Service\API\Response\SubmitGroupResponse;
use DiceRobot\Service\API\Response\UpdateCardResponse;
use DiceRobot\Service\API\Response\UpdateRobotResponse;
use DiceRobot\Service\APIService;

/**
 * APIs of DiceRobot, which is provided by Drsanwujiang. Please do NOT factitious query through or submit data to these
 * APIs.
 */
class DiceRobotAPI extends APIService
{
    protected static string $prefix;

    protected bool $h2 = true;

    /**
     * Update robot online info.
     *
     * @param int $selfId QQ ID of the robot
     *
     * @throws InternalErrorException
     * @throws JSONDecodeException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    public function updateRobot(int $selfId): void
    {
        $url = static::$prefix . "/robot/{$selfId}";
        $method = "PATCH";

        new UpdateRobotResponse(self::request($url, $method));
    }

    /**
     * Get access token.
     *
     * @param int $selfId QQ ID of the robot
     * @param int $userId QQ ID of message sender
     *
     * @throws InternalErrorException
     * @throws JSONDecodeException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    public function auth(int $selfId, int $userId = NULL): void
    {
        if (is_null($userId))
            $url = static::$prefix . "/robot/{$selfId}/auth";
        else
            $url = static::$prefix . "/robot/{$selfId}/auth/{$userId}";

        $response = new AuthorizeResponse(self::request($url));
        $this->auth = $response->token;
    }

    /**
     * Query if the group is delinquent.
     *
     * @param int $groupId Group ID
     *
     * @return QueryGroupResponse The response
     *
     * @throws InternalErrorException
     * @throws JSONDecodeException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    public function queryGroup(int $groupId): QueryGroupResponse
    {
        $url = static::$prefix . "/group/{$groupId}";

        return new QueryGroupResponse(self::request($url));
    }

    /**
     * Submit ID of the delinquent group to public database. These group IDs will be queried when DiceRobot is added
     * to a group.
     *
     * @param int $groupId Delinquent group ID
     *
     * @return SubmitGroupResponse The response
     *
     * @throws InternalErrorException
     * @throws JSONDecodeException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    public function submitGroup(int $groupId): SubmitGroupResponse
    {
        $url = static::$prefix . "/group/{$groupId}";
        $method = "PUT";

        return new SubmitGroupResponse(self::request($url, $method));
    }

    /**
     * Get character card data.
     *
     * @param int $cardId Character card ID
     *
     * @return GetCardResponse The response
     *
     * @throws InternalErrorException
     * @throws JSONDecodeException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    public function getCard(int $cardId): GetCardResponse
    {
        $url = static::$prefix . "/card/{$cardId}";

        return new GetCardResponse(self::request($url));
    }

    /**
     * Update character card data.
     *
     * @param int $cardId Character card ID
     * @param string $attribute Attribute name
     * @param int $change Change in attribute
     *
     * @return UpdateCardResponse The response
     *
     * @throws InternalErrorException
     * @throws JSONDecodeException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    public function updateCard(int $cardId, string $attribute, int $change): UpdateCardResponse
    {
        $url = static::$prefix . "/card/{$cardId}";
        $method = "PATCH";
        $data = [
            "attribute" => $attribute,
            "change" => $change
        ];

        return new UpdateCardResponse(self::request($url, $method, $data));
    }

    /**
     * Sanity check.
     *
     * @param int $cardId Character card ID
     * @param int $checkResult Sanity check result
     * @param array $decreases Sanity decreases
     *
     * @return SanityCheckResponse The response
     *
     * @throws InternalErrorException
     * @throws JSONDecodeException
     * @throws NetworkErrorException
     * @throws UnexpectedErrorException
     */
    public function sc(int $cardId, int $checkResult, array $decreases): SanityCheckResponse
    {
        $url = static::$prefix . "/card/{$cardId}/sc";
        $method = "PATCH";
        $data = [
            "check_result" => $checkResult,
            "decreases" => $decreases
        ];

        return new SanityCheckResponse(self::request($url, $method, $data));
    }
}
