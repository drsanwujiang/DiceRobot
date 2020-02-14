<?php
namespace DiceRobot\Base;

/**
 * Class API
 *
 * All APIs that robot may use. Most of the APIs are from HTTP API plugin, the other are from Drsanwujiang.
 */
final class API
{
    private static function curlPost(string $url, ?string $data = NULL)
    {
        $ch = curl_init($url);

        if (!is_null($data))
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public static function getAPICredential(int $selfId): array
    {
        /*
         * Get credential which will be submitted when robot submits delinquent group ID.
         * Please DO NOT query through this API factitious.
         */

        $url = CUSTOM_API_URL["getAPICredential"];
        $timestamp = time();
        $data = json_encode(array("robot_id" => $selfId, "timestamp" => $timestamp,
            "token" => sha1($selfId + $timestamp)));

        return json_decode(self::curlPost($url, $data), true);
    }

    public static function getGroupInfo(int $groupId, bool $noCache = false): array
    {
        $url = HTTP_API_URL["getGroupInfo"];
        $data = json_encode(array("group_id" => $groupId, "no_cache" => $noCache));

        return json_decode(self::curlPost($url, $data), true);
    }

    public static function getGroupMemberInfo(int $groupId, int $userId, bool $noCache = false): array
    {
        $url = HTTP_API_URL["getGroupMemberInfo"];
        $data = json_encode(array("group_id" => $groupId, "user_id" => $userId, "no_cache" => $noCache));

        return json_decode(self::curlPost($url, $data), true);
    }

    public static function getLoginInfo(): array
    {
        $url = HTTP_API_URL["getLoginInfo"];

        return json_decode(self::curlPost($url), true);
    }

    public static function queryDelinquentGroup(int $groupId): array
    {
        /*
         * Submit delinquent group ID to public database. These group ID will be queried when DiceRobot is added to a
         * group.
         * Please DO NOT query through this API factitious.
         */

        $url = CUSTOM_API_URL["queryDelinquentGroup"];
        $data = json_encode(array("group_id" => $groupId));

        return json_decode(self::curlPost($url, $data), true);
    }

    public static function sendDiscussMessage(int $discussId, string $message, bool $autoEscape = false): void
    {
        $url = HTTP_API_URL["sendDiscussMessage"];
        $data = json_encode(array("discuss_id" => $discussId, "message" => $message, "auto_escape" => $autoEscape));

        self::curlPost($url, $data);
    }

    public static function sendGroupMessage(int $groupId, string $message, bool $autoEscape = false): void
    {
        $url = HTTP_API_URL["sendGroupMessage"];
        $data = json_encode(array("group_id" => $groupId, "message" => $message, "auto_escape" => $autoEscape));

        self::curlPost($url, $data);
    }

    public static function sendGroupMessageAsync(int $groupId, string $message, bool $autoEscape = false): void
    {
        $url = HTTP_API_URL["sendGroupMessage"] . "_async";
        $data = json_encode(array("group_id" => $groupId, "message" => $message, "auto_escape" => $autoEscape));

        self::curlPost($url, $data);
    }

    public static function sendPrivateMessageAsync(int $userId, string $message, bool $autoEscape = false): void
    {
        $url = HTTP_API_URL["sendPrivateMessage"] . "_async";
        $data = json_encode(array("user_id" => $userId, "message" => $message, "auto_escape" => $autoEscape));

        self::curlPost($url, $data);
    }

    public static function setDiscussLeaveAsync(int $discussId): void
    {
        $url = HTTP_API_URL["setDiscussLeave"] . "_async";
        $data = json_encode(array("discuss_id" => $discussId));

        self::curlPost($url, $data);
    }

    public static function setFriendAddRequestAsync(string $flag, bool $approve, ?string $remark = NULL): void
    {
        $url = HTTP_API_URL["setFriendAddRequest"] . "_async";
        $data = json_encode(array("flag" => $flag, "approve" => $approve, "remark" => $remark));

        self::curlPost($url, $data);
    }

    public static function setGroupAddRequestAsync(string $flag, string $subType, bool $approve,
                                                   ?string $reason = NULL): array
    {
        $url = HTTP_API_URL["setGroupAddRequest"] . "_async";
        $data = json_encode(array("flag" => $flag, "sub_type" => $subType, "approve" => $approve, "reason" => $reason));

        return json_decode(self::curlPost($url, $data), true);
    }

    public static function setGroupCardAsync(int $groupId, int $userId, string $card): void
    {
        $url = HTTP_API_URL["setGroupCard"] . "_async";
        $data = json_encode(array("group_id" => $groupId, "user_id" => $userId, "card" => $card));

        self::curlPost($url, $data);
    }

    public static function setGroupLeaveAsync(int $groupId, bool $isDismiss = false): void
    {
        $url = HTTP_API_URL["setGroupLeave"] . "_async";
        $data = json_encode(array("group_id" => $groupId, "is_dismiss" => $isDismiss));

        self::curlPost($url, $data);
    }

    public static function submitDelinquentGroup(int $groupId, string $credential): array
    {
        /*
         * Submit delinquent group ID to public database. These group ID will be queried when DiceRobot is added to a
         * group.
         * Please DO NOT submit data to this API factitious.
         */

        $url = CUSTOM_API_URL["submitDelinquentGroup"];
        $data = json_encode(array("group_id" => $groupId, "credential" => $credential));

        return json_decode(self::curlPost($url, $data), true);
    }

    public static function heartbeatReport(int $selfId): void
    {
        /*
         * Report to database. This report will be queried only when robot submits delinquent group ID.
         * Please DO NOT submit data to this API factitious.
         */

        $url = CUSTOM_API_URL["heartbeatReport"];
        $timestamp = time();
        $data = json_encode(array("robot_id" => $selfId, "timestamp" => $timestamp,
            "token" => sha1($selfId + $timestamp)));

        self::curlPost($url, $data);
    }
}
