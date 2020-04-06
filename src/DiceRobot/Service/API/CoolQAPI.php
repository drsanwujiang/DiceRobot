<?php
namespace DiceRobot\Service\API;

use DiceRobot\Exception\InformativeException\APIException\InternalErrorException;
use DiceRobot\Exception\InformativeException\APIException\NetworkErrorException;
use DiceRobot\Service\APIService;

/**
 * APIs of CoolQ, which is provided by CoolQ HTTP API plugin, visit
 * https://cqhttp.cc/docs/#/API?id=api-%E5%88%97%E8%A1%A8 for details of these APIs.
 */
class CoolQAPI extends APIService
{
    protected static string $prefix;

    protected bool $h2 = false;

    protected function decode(string $content): array
    {
        return json_decode($content, true);
    }

    /**
     * @param int $groupId
     * @param bool $noCache
     *
     * @return array
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    public function getGroupInfo(int $groupId, bool $noCache = false): array
    {
        $url = static::$prefix . "/get_group_info";
        $method = "POST";
        $data = [
            "group_id" => $groupId,
            "no_cache" => $noCache
        ];

        return $this->decode(self::request($url, $method, $data))["data"];
    }

    /**
     * @param int $groupId
     * @param int $userId
     * @param bool $noCache
     *
     * @return array
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    public function getGroupMemberInfo(int $groupId, int $userId, bool $noCache = false): array
    {
        $url = static::$prefix . "/get_group_member_info";
        $method = "POST";
        $data = [
            "group_id" => $groupId,
            "user_id" => $userId,
            "no_cache" => $noCache
        ];

        return $this->decode(self::request($url, $method, $data))["data"];
    }

    /**
     * @return array
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    public function getLoginInfo(): array
    {
        $url = static::$prefix . "/get_login_info";

        return $this->decode(self::request($url))["data"];
    }

    /**
     * @param int $discussId
     * @param string $message
     * @param bool $autoEscape
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    public function sendDiscussMessage(int $discussId, string $message, bool $autoEscape = false): void
    {
        $url = static::$prefix . "/send_discuss_msg";
        $method = "POST";
        $data = [
            "discuss_id" => $discussId,
            "message" => $message,
            "auto_escape" => $autoEscape
        ];

        self::request($url, $method, $data);
    }

    /**
     * @param int $discussId
     * @param string $message
     * @param bool $autoEscape
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    public function sendDiscussMessageAsync(int $discussId, string $message, bool $autoEscape = false): void
    {
        $url = static::$prefix . "/send_discuss_msg_async";
        $method = "POST";
        $data = [
            "discuss_id" => $discussId,
            "message" => $message,
            "auto_escape" => $autoEscape
        ];

        self::request($url, $method, $data);
    }

    /**
     * @param int $groupId
     * @param string $message
     * @param bool $autoEscape
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    public function sendGroupMessage(int $groupId, string $message, bool $autoEscape = false): void
    {
        $url = static::$prefix . "/send_group_msg";
        $method = "POST";
        $data = [
            "group_id" => $groupId,
            "message" => $message,
            "auto_escape" => $autoEscape
        ];

        self::request($url, $method, $data);
    }

    /**
     * @param int $groupId
     * @param string $message
     * @param bool $autoEscape
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    public function sendGroupMessageAsync(int $groupId, string $message, bool $autoEscape = false): void
    {
        $url = static::$prefix . "/send_group_msg_async";
        $method = "POST";
        $data = [
            "group_id" => $groupId,
            "message" => $message,
            "auto_escape" => $autoEscape
        ];

        self::request($url, $method, $data);
    }

    /**
     * @param int $userId
     * @param string $message
     * @param bool $autoEscape
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    public function sendPrivateMessageAsync(int $userId, string $message, bool $autoEscape = false): void
    {
        $url = static::$prefix . "/send_private_msg_async";
        $method = "POST";
        $data = [
            "user_id" => $userId,
            "message" => $message,
            "auto_escape" => $autoEscape
        ];

        self::request($url, $method, $data);
    }

    /**
     * @param int $discussId
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    public function setDiscussLeaveAsync(int $discussId): void
    {
        $url = static::$prefix . "/set_discuss_leave_async";
        $method = "POST";
        $data = [
            "discuss_id" => $discussId
        ];

        self::request($url, $method, $data);
    }

    /**
     * @param string $flag
     * @param bool $approve
     * @param string|NULL $remark
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    public function setFriendAddRequestAsync(string $flag, bool $approve, string $remark = NULL): void
    {
        $url = static::$prefix . "/set_friend_add_request_async";
        $method = "POST";
        $data = [
            "flag" => $flag,
            "approve" => $approve,
            "remark" => $remark
        ];

        self::request($url, $method, $data);
    }

    /**
     * @param string $flag
     * @param string $subType
     * @param bool $approve
     * @param string|NULL $reason
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    public function setGroupAddRequestAsync(
        string $flag,
        string $subType,
        bool $approve,
        string $reason = NULL
    ): void {
        $url = static::$prefix . "/set_group_add_request_async";
        $method = "POST";
        $data = [
            "flag" => $flag,
            "sub_type" => $subType,
            "approve" => $approve,
            "reason" => $reason
        ];

        self::request($url, $method, $data);
    }

    /**
     * @param int $groupId
     * @param int $userId
     * @param string $card
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    public function setGroupCardAsync(int $groupId, int $userId, string $card): void
    {
        $url = static::$prefix . "/set_group_card_async";
        $method = "POST";
        $data = [
            "group_id" => $groupId,
            "user_id" => $userId,
            "card" => $card
        ];

        self::request($url, $method, $data);
    }

    /**
     * @param int $groupId
     * @param bool $isDismiss
     *
     * @throws InternalErrorException
     * @throws NetworkErrorException
     */
    public function setGroupLeaveAsync(int $groupId, bool $isDismiss = false): void
    {
        $url = static::$prefix . "/set_group_leave_async";
        $method = "POST";
        $data = [
            "group_id" => $groupId,
            "is_dismiss" => $isDismiss
        ];

        self::request($url, $method, $data);
    }
}
