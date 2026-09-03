"""平台协议中的各类枚举常量。"""

from __future__ import annotations

from enum import IntEnum, StrEnum

__all__ = ["EventType", "FileType", "MessageType", "OpCode"]


class OpCode(IntEnum):
    """Webhook payload 的操作码。

    Attributes:
        DISPATCH: 服务端推送的业务事件。
        HTTP_CALLBACK_ACK: 对推送事件的确认。
        CALLBACK_VALIDATION: 配置回调地址时下发的 challenge，需用私钥签名后回传。
    """

    DISPATCH = 0
    HTTP_CALLBACK_ACK = 12
    CALLBACK_VALIDATION = 13


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
