"""平台协议中的各类枚举常量。"""

from __future__ import annotations

from enum import IntEnum, StrEnum

__all__ = ["EventType", "FileType", "MessageType", "OpCode"]


class OpCode(IntEnum):
    """Webhook payload 的操作码。"""

    DISPATCH = 0
    """服务端推送的业务事件。"""

    HTTP_CALLBACK_ACK = 12
    """对推送事件的确认。"""

    CALLBACK_VALIDATION = 13
    """配置回调地址时下发的 challenge，需用私钥签名后回传。"""


class EventType(StrEnum):
    """已订阅的事件类型。"""

    GROUP_AT_MESSAGE_CREATE = "GROUP_AT_MESSAGE_CREATE"
    """群内 @ 机器人的消息。公域机器人的默认能力。"""

    GROUP_MESSAGE_CREATE = "GROUP_MESSAGE_CREATE"
    """群内全量消息。需向平台单独申请权限，未获批时不会收到。"""

    C2C_MESSAGE_CREATE = "C2C_MESSAGE_CREATE"
    """单聊消息。"""

    GROUP_ADD_ROBOT = "GROUP_ADD_ROBOT"
    GROUP_DEL_ROBOT = "GROUP_DEL_ROBOT"
    FRIEND_ADD = "FRIEND_ADD"
    FRIEND_DEL = "FRIEND_DEL"


class MessageType(IntEnum):
    """发送消息时的 ``msg_type``。"""

    TEXT = 0
    MIXED = 1
    MARKDOWN = 2
    ARK = 3
    EMBED = 4
    MEDIA = 7


class FileType(IntEnum):
    """富媒体上传时的 ``file_type``。"""

    IMAGE = 1
    VIDEO = 2
    VOICE = 3
    FILE = 4
    """平台暂未开放。"""
