<?php
namespace DiceRobot\Base;

/**
 * Utility class. These encapsulated methods will call APIs that robot may use.
 */
final class API
{
    /**
     * Send a post request via cURL.
     *
     * @param string $url URl to access
     * @param array|null $data Data to post
     *
     * @return bool|string Returned content
     */
    private static function curlPost(string $url, ?array $data = NULL)
    {
        $ch = curl_init($url);

        if (!is_null($data))
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /* API of HTTP API plugin */

    public static function getGroupInfo(int $groupId, bool $noCache = false): array
    {
        $url = HTTP_API_URL["getGroupInfo"];
        $data = [
            "group_id" => $groupId,
            "no_cache" => $noCache
        ];

        return json_decode(self::curlPost($url, $data), true);
    }

    public static function getGroupMemberInfo(int $groupId, int $userId, bool $noCache = false): array
    {
        $url = HTTP_API_URL["getGroupMemberInfo"];
        $data = [
            "group_id" => $groupId,
            "user_id" => $userId,
            "no_cache" => $noCache
        ];

        return json_decode(self::curlPost($url, $data), true);
    }

    public static function getLoginInfo(): array
    {
        $url = HTTP_API_URL["getLoginInfo"];

        return json_decode(self::curlPost($url), true);
    }

    public static function sendDiscussMessage(int $discussId, string $message, bool $autoEscape = false): void
    {
        $url = HTTP_API_URL["sendDiscussMessage"];
        $data = [
            "discuss_id" => $discussId,
            "message" => $message,
            "auto_escape" => $autoEscape
        ];

        self::curlPost($url, $data);
    }

    public static function sendDiscussMessageAsync(int $discussId, string $message, bool $autoEscape = false): void
    {
        $url = HTTP_API_URL["sendDiscussMessage"] . "_async";
        $data = [
            "discuss_id" => $discussId,
            "message" => $message,
            "auto_escape" => $autoEscape
        ];

        self::curlPost($url, $data);
    }

    public static function sendGroupMessage(int $groupId, string $message, bool $autoEscape = false): void
    {
        $url = HTTP_API_URL["sendGroupMessage"];
        $data = [
            "group_id" => $groupId,
            "message" => $message,
            "auto_escape" => $autoEscape
        ];

        self::curlPost($url, $data);
    }

    public static function sendGroupMessageAsync(int $groupId, string $message, bool $autoEscape = false): void
    {
        $url = HTTP_API_URL["sendGroupMessage"] . "_async";
        $data = [
            "group_id" => $groupId,
            "message" => $message,
            "auto_escape" => $autoEscape
        ];

        self::curlPost($url, $data);
    }

    public static function sendPrivateMessageAsync(int $userId, string $message, bool $autoEscape = false): void
    {
        $url = HTTP_API_URL["sendPrivateMessage"] . "_async";
        $data = [
            "user_id" => $userId,
            "message" => $message,
            "auto_escape" => $autoEscape
        ];

        self::curlPost($url, $data);
    }

    public static function setDiscussLeaveAsync(int $discussId): void
    {
        $url = HTTP_API_URL["setDiscussLeave"] . "_async";
        $data = [
            "discuss_id" => $discussId
        ];

        self::curlPost($url, $data);
    }

    public static function setFriendAddRequestAsync(string $flag, bool $approve, ?string $remark = NULL): void
    {
        $url = HTTP_API_URL["setFriendAddRequest"] . "_async";
        $data = [
            "flag" => $flag,
            "approve" => $approve,
            "remark" => $remark
        ];

        self::curlPost($url, $data);
    }

    public static function setGroupAddRequestAsync(string $flag, string $subType, bool $approve,
                                                   ?string $reason = NULL): array
    {
        $url = HTTP_API_URL["setGroupAddRequest"] . "_async";
        $data = [
            "flag" => $flag,
            "sub_type" => $subType,
            "approve" => $approve,
            "reason" => $reason
        ];

        return json_decode(self::curlPost($url, $data), true);
    }

    public static function setGroupCardAsync(int $groupId, int $userId, string $card): void
    {
        $url = HTTP_API_URL["setGroupCard"] . "_async";
        $data = [
            "group_id" => $groupId,
            "user_id" => $userId,
            "card" => $card
        ];

        self::curlPost($url, $data);
    }

    public static function setGroupLeaveAsync(int $groupId, bool $isDismiss = false): void
    {
        $url = HTTP_API_URL["setGroupLeave"] . "_async";
        $data = [
            "group_id" => $groupId,
            "is_dismiss" => $isDismiss
        ];

        self::curlPost($url, $data);
    }

    /* API of Drsanwujiang. Please do NOT factitious query through or submit data to this API. */

    /**
     * Get credential which will be submitted when robot queries data from public database.
     *
     * @param int $selfId QQ ID of robot
     *
     * @return array Returned data
     */
    public static function getAPICredential(int $selfId): array
    {
        $url = CUSTOM_API_URL["getAPICredential"];
        $timestamp = time();
        $data = [
            "robot_id" => $selfId,
            "timestamp" => $timestamp,
            "token" => sha1($selfId + $timestamp)
        ];

        return json_decode(self::curlPost($url, $data), true);
    }

    /**
     * Get character card data.
     *
     * @param int $userId QQ ID of message sender
     * @param int $cardId Character card ID
     * @param string $credential Credential
     *
     * @return array Returned data
     */
    public static function getCharacterCard(int $userId, int $cardId, string $credential): array
    {
        $url = CUSTOM_API_URL["getCharacterCard"];
        $data = [
            "user_id" => $userId,
            "card_id" => $cardId,
            "credential" => $credential
        ];

        return json_decode(self::curlPost($url, $data), true);
    }

    /**
     * Report to database. This report will be queried when robot requests credential.
     *
     * @param int $selfId QQ ID of robot
     */
    public static function heartbeatReport(int $selfId): void
    {
        $url = CUSTOM_API_URL["heartbeatReport"];
        $timestamp = time();
        $data = [
            "robot_id" => $selfId,
            "timestamp" => $timestamp,
            "token" => sha1($selfId + $timestamp)
        ];

        self::curlPost($url, $data);
    }

    /**
     * Query if the group is delinquent.
     *
     * @param int $groupId Group ID to query
     *
     * @return array Returned data
     */
    public static function queryDelinquentGroup(int $groupId): array
    {
        $url = CUSTOM_API_URL["queryDelinquentGroup"];
        $data = [
            "group_id" => $groupId
        ];

        return json_decode(self::curlPost($url, $data), true);
    }

    /**
     * Sanity check.
     *
     * @param int $userId QQ ID of message sender
     * @param int $cardId Character card ID
     * @param int $checkResult Sanity check result
     * @param array $decreases Sanity decreases
     * @param string $credential Credential
     *
     * @return array Returned data
     */
    public static function sanityCheck(
        int $userId,
        int $cardId,
        int $checkResult,
        array $decreases,
        string $credential
    ): array {
        $url = CUSTOM_API_URL["sanityCheck"];
        $data = [
            "user_id" => $userId,
            "card_id" => $cardId,
            "check_result" => $checkResult,
            "decreases" => $decreases,
            "credential" => $credential
        ];

        return json_decode(self::curlPost($url, $data), true);
    }

    /**
     * Submit delinquent group ID to public database. These group ID will be queried when DiceRobot is added to group.
     *
     * @param int $groupId Group ID to submit
     * @param string $credential Credential
     *
     * @return array Returned data
     */
    public static function submitDelinquentGroup(int $groupId, string $credential): array
    {
        $url = CUSTOM_API_URL["submitDelinquentGroup"];
        $data = [
            "group_id" => $groupId,
            "credential" => $credential
        ];

        return json_decode(self::curlPost($url, $data), true);
    }

    /**
     * Update character card data.
     *
     * @param int $userId QQ ID of message sender
     * @param int $cardId Character card ID
     * @param string $attributeName Attribute name
     * @param bool $addition Addition or subtraction
     * @param int $value Value
     * @param string $credential Credential
     *
     * @return array Returned data
     */
    public static function updateCharacterCard(
        int $userId,
        int $cardId,
        string $attributeName,
        bool $addition,
        int $value,
        string $credential
    ): array {
        $url = CUSTOM_API_URL["updateCharacterCard"];
        $data = [
            "user_id" => $userId,
            "card_id" => $cardId,
            "attribute_name" => $attributeName,
            "addition" => $addition,
            "value" => $value,
            "credential" => $credential
        ];

        return json_decode(self::curlPost($url, $data), true);
    }
}
