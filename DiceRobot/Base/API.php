<?php
namespace DiceRobot\Base;

/**
 * Class API
 *
 * All APIs that robot may use. Most of the APIs are from HTTP API plugin, the other are from Drsanwujiang.
 */
final class API
{
    /**
     * Send a post request via cURL.
     *
     * @param string $url URl to access
     * @param string|null $data Data to post
     *
     * @return bool|string Returned content
     */
    private static function curlPost(string $url, ?string $data = NULL)
    {
        $ch = curl_init($url);

        if (!is_null($data))
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

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
        $data = json_encode(["group_id" => $groupId, "no_cache" => $noCache]);

        return json_decode(self::curlPost($url, $data), true);
    }

    public static function getGroupMemberInfo(int $groupId, int $userId, bool $noCache = false): array
    {
        $url = HTTP_API_URL["getGroupMemberInfo"];
        $data = json_encode(["group_id" => $groupId, "user_id" => $userId, "no_cache" => $noCache]);

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
        $data = json_encode(["discuss_id" => $discussId, "message" => $message, "auto_escape" => $autoEscape]);

        self::curlPost($url, $data);
    }

    public static function sendDiscussMessageAsync(int $discussId, string $message, bool $autoEscape = false): void
    {
        $url = HTTP_API_URL["sendDiscussMessage"] . "_async";
        $data = json_encode(["discuss_id" => $discussId, "message" => $message, "auto_escape" => $autoEscape]);

        self::curlPost($url, $data);
    }

    public static function sendGroupMessage(int $groupId, string $message, bool $autoEscape = false): void
    {
        $url = HTTP_API_URL["sendGroupMessage"];
        $data = json_encode(["group_id" => $groupId, "message" => $message, "auto_escape" => $autoEscape]);

        self::curlPost($url, $data);
    }

    public static function sendGroupMessageAsync(int $groupId, string $message, bool $autoEscape = false): void
    {
        $url = HTTP_API_URL["sendGroupMessage"] . "_async";
        $data = json_encode(["group_id" => $groupId, "message" => $message, "auto_escape" => $autoEscape]);

        self::curlPost($url, $data);
    }

    public static function sendPrivateMessageAsync(int $userId, string $message, bool $autoEscape = false): void
    {
        $url = HTTP_API_URL["sendPrivateMessage"] . "_async";
        $data = json_encode(["user_id" => $userId, "message" => $message, "auto_escape" => $autoEscape]);

        self::curlPost($url, $data);
    }

    public static function setDiscussLeaveAsync(int $discussId): void
    {
        $url = HTTP_API_URL["setDiscussLeave"] . "_async";
        $data = json_encode(["discuss_id" => $discussId]);

        self::curlPost($url, $data);
    }

    public static function setFriendAddRequestAsync(string $flag, bool $approve, ?string $remark = NULL): void
    {
        $url = HTTP_API_URL["setFriendAddRequest"] . "_async";
        $data = json_encode(["flag" => $flag, "approve" => $approve, "remark" => $remark]);

        self::curlPost($url, $data);
    }

    public static function setGroupAddRequestAsync(string $flag, string $subType, bool $approve,
                                                   ?string $reason = NULL): array
    {
        $url = HTTP_API_URL["setGroupAddRequest"] . "_async";
        $data = json_encode(["flag" => $flag, "sub_type" => $subType, "approve" => $approve, "reason" => $reason]);

        return json_decode(self::curlPost($url, $data), true);
    }

    public static function setGroupCardAsync(int $groupId, int $userId, string $card): void
    {
        $url = HTTP_API_URL["setGroupCard"] . "_async";
        $data = json_encode(["group_id" => $groupId, "user_id" => $userId, "card" => $card]);

        self::curlPost($url, $data);
    }

    public static function setGroupLeaveAsync(int $groupId, bool $isDismiss = false): void
    {
        $url = HTTP_API_URL["setGroupLeave"] . "_async";
        $data = json_encode(["group_id" => $groupId, "is_dismiss" => $isDismiss]);

        self::curlPost($url, $data);
    }

    /* API of Drsanwujiang. Please do NOT query through or submit data to this API factitious. */

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
        $data = json_encode(["robot_id" => $selfId, "timestamp" => $timestamp,
            "token" => sha1($selfId + $timestamp)]);

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
        $data = json_encode(["user_id" => $userId, "card_id" => $cardId, "credential" => $credential]);

        return json_decode(self::curlPost($url, $data), true);
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
        $data = json_encode(["group_id" => $groupId]);

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
        $data = json_encode(["group_id" => $groupId, "credential" => $credential]);

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
        $data = json_encode(["robot_id" => $selfId, "timestamp" => $timestamp,
            "token" => sha1($selfId + $timestamp)]);

        self::curlPost($url, $data);
    }
}
