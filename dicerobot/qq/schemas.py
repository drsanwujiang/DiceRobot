"""Webhook payload 与 OpenAPI 请求、响应的数据模型。"""

from __future__ import annotations

from typing import Any

from pydantic import BaseModel, Field

__all__ = [
    "C2CMessage",
    "FriendEvent",
    "GroupMessage",
    "GroupRobotEvent",
    "Payload",
    "SendMessageResult",
    "ValidationData",
    "ValidationResponse",
]


class Payload(BaseModel):
    """Webhook 上行 payload 的信封。

    ``op`` 保留为 ``int`` 而非枚举，使平台新增操作码时未知取值被安全忽略而非解析失败。
    事件数据 ``d`` 同样延后到确定 ``t`` 之后再解析。
    """

    op: int
    d: dict[str, Any] = Field(default_factory=dict)
    t: str | None = None
    id: str | None = Field(default=None, description="事件唯一 ID，用于幂等去重")
    s: int | None = None


class ValidationData(BaseModel):
    """``op = 13`` 回调地址校验的下发数据。"""

    plain_token: str
    event_ts: str


class ValidationResponse(BaseModel):
    """回调地址校验的响应体。"""

    plain_token: str
    signature: str


class _GroupAuthor(BaseModel):
    member_openid: str
    """发送者在该群内的标识，与单聊的 user_openid 不互通。"""


class _C2CAuthor(BaseModel):
    user_openid: str


class _MessageBase(BaseModel):
    id: str
    """消息 ID，被动回复时作为 msg_id 传回。"""

    content: str = ""

    timestamp: str | None = None
    """平台侧时间戳，仅供参考。

    被动回复窗口以本地收到事件的时刻起算，该时刻更晚因而更保守，不会因投递延迟
    而误判窗口余量。
    """


class GroupMessage(_MessageBase):
    """``GROUP_AT_MESSAGE_CREATE`` 与 ``GROUP_MESSAGE_CREATE`` 的事件数据。"""

    group_openid: str
    author: _GroupAuthor


class C2CMessage(_MessageBase):
    """``C2C_MESSAGE_CREATE`` 的事件数据。"""

    author: _C2CAuthor


class GroupRobotEvent(BaseModel):
    """机器人被加入或移出群聊的事件数据。"""

    group_openid: str

    op_member_openid: str | None = None
    """操作者在该群内的标识。平台未提供时为空。"""


class FriendEvent(BaseModel):
    """用户添加或删除机器人的事件数据。"""

    openid: str


class SendMessageResult(BaseModel):
    """发送消息接口的响应。"""

    id: str | None = None
    timestamp: str | int | None = None
