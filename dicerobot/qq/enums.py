"""平台协议中的各类枚举常量。"""

from __future__ import annotations

from enum import IntEnum, StrEnum

__all__ = ["CloseCode", "EventType", "FileType", "MessageType", "OpCode"]


class OpCode(IntEnum):
    """payload 的操作码。Webhook 与 WebSocket 网关共用一套取值，各自只用到其中一部分。

    Attributes:
        DISPATCH: 服务端推送的业务事件，两种接入方式都用它下发。
        HEARTBEAT: 客户端上行的心跳，``d`` 为最后收到的 ``s``。
        IDENTIFY: 客户端上行的鉴权。
        RESUME: 客户端上行的会话恢复，平台随后补发断连期间遗漏的事件。
        RECONNECT: 平台要求客户端重连，会话仍然有效，重连后应 RESUME。
        INVALID_SESSION: 鉴权或恢复被拒，会话不再可用，须重新 IDENTIFY。
        HELLO: 连接建立后平台下发的第一帧，含心跳间隔。
        HEARTBEAT_ACK: 心跳的应答。
        HTTP_CALLBACK_ACK: 对推送事件的确认。
        CALLBACK_VALIDATION: 配置回调地址时下发的 challenge，需用私钥签名后回传。
    """

    DISPATCH = 0
    HEARTBEAT = 1
    IDENTIFY = 2
    RESUME = 6
    RECONNECT = 7
    INVALID_SESSION = 9
    HELLO = 10
    HEARTBEAT_ACK = 11
    HTTP_CALLBACK_ACK = 12
    CALLBACK_VALIDATION = 13


class CloseCode(IntEnum):
    """WebSocket 网关的关闭码。只列出需要区别处置的取值，其余一律按可重连处理。

    Attributes:
        AUTHENTICATION_FAILED: 鉴权失败，多为 token 已被吊销，作废本地缓存后重连。
        INVALID_SESSION: session_id 无效，不能继续恢复，须重新鉴权。
        INVALID_SEQ: seq 错误，同样只能重新鉴权。
        RATE_LIMITED: 上行 payload 发送过快。
        SESSION_EXPIRED: 连接过期，重连后恢复会话即可。
        INVALID_SHARD: 分片参数非法。
        SHARDING_REQUIRED: 事件量要求分片接入。
        INVALID_VERSION: 协议版本非法。
        INVALID_INTENTS: intents 取值非法。
        INTENTS_NOT_AUTHORIZED: 声明了未获授权的 intents。
        BOT_OFFLINE: 机器人已下架。
        BOT_BANNED: 机器人已封禁。
    """

    AUTHENTICATION_FAILED = 4004
    INVALID_SESSION = 4006
    INVALID_SEQ = 4007
    RATE_LIMITED = 4008
    SESSION_EXPIRED = 4009
    INVALID_SHARD = 4010
    SHARDING_REQUIRED = 4011
    INVALID_VERSION = 4012
    INVALID_INTENTS = 4013
    INTENTS_NOT_AUTHORIZED = 4014
    BOT_OFFLINE = 4914
    BOT_BANNED = 4915


class EventType(StrEnum):
    """已订阅的事件类型。

    Attributes:
        GROUP_AT_MESSAGE_CREATE: 群内 @ 机器人的消息。正文已由平台剥去 @ 前缀。
        GROUP_MESSAGE_CREATE: 群内全量消息。是否推送由群主或管理员在群内设置，不是机器人
            可申请的权限；该设置变化时平台推送 GROUP_MSG_RECEIVE / GROUP_MSG_REJECT，本
            项目不订阅，因而不假定当前处于哪种模式。
        C2C_MESSAGE_CREATE: 单聊消息。
    """

    GROUP_AT_MESSAGE_CREATE = "GROUP_AT_MESSAGE_CREATE"
    GROUP_MESSAGE_CREATE = "GROUP_MESSAGE_CREATE"
    C2C_MESSAGE_CREATE = "C2C_MESSAGE_CREATE"
    GROUP_ADD_ROBOT = "GROUP_ADD_ROBOT"
    GROUP_DEL_ROBOT = "GROUP_DEL_ROBOT"
    FRIEND_ADD = "FRIEND_ADD"
    FRIEND_DEL = "FRIEND_DEL"


class MessageType(IntEnum):
    """发送消息时的 ``msg_type``。V2 的群聊与单聊接口只定义了这三种。"""

    TEXT = 0
    MARKDOWN = 2
    MEDIA = 7


class FileType(IntEnum):
    """富媒体上传时的 ``file_type``。"""

    IMAGE = 1
    VIDEO = 2
    VOICE = 3
    FILE = 4
