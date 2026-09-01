"""把平台事件归一化为与场景无关的模型。

群聊与单聊在平台侧字段不同，全量群消息与 @ 消息又是两个事件类型。归一化后，路由、
配额与插件实现只需面对一种形状。

消息与非消息事件都可以被动回复：前者携带 ``msg_id``，后者携带 ``event_id``。两者
通过 :class:`ReplyTarget` 统一，回复配额的计量因此不必区分来源。
"""

from __future__ import annotations

import re
from collections.abc import Sequence
from dataclasses import dataclass
from datetime import datetime
from typing import Any

from loguru import logger

from dicerobot.enums import MemberRole, Scene
from dicerobot.qq.enums import EventType
from dicerobot.qq.schemas import C2CMessage, FriendEvent, GroupMessage, GroupRobotEvent, Mention, Payload

__all__ = ["IncomingEvent", "IncomingMessage", "ReplyTarget", "normalize_event", "normalize_message"]

# @ 标记形如 <@openid>，其中 openid 是十六进制串而非数字；按数字匹配将无法命中任何标记。
# 被 @ 的既可能是机器人自身，也可能是其他成员，两者形式相同，一并剥离。
_MENTION_PATTERN = re.compile(r"<@!?[^<>\s]+>")

_MESSAGE_EVENTS = frozenset(
    {
        EventType.GROUP_AT_MESSAGE_CREATE,
        EventType.GROUP_MESSAGE_CREATE,
        EventType.C2C_MESSAGE_CREATE,
    }
)

_GROUP_ROBOT_EVENTS = frozenset({EventType.GROUP_ADD_ROBOT, EventType.GROUP_DEL_ROBOT})
_FRIEND_EVENTS = frozenset({EventType.FRIEND_ADD, EventType.FRIEND_DEL})


@dataclass(frozen=True, slots=True)
class ReplyTarget:
    """被动回复的去向与凭据。

    Attributes:
        scene: 场景，决定发送接口与配额。
        scene_id: 会话标识。
        msg_id: 所回复消息的 ID，事件来源时为空。
        event_id: 所回复事件的 ID，消息来源时为空。
        received_at: 本地收到的时刻，回复窗口以此起算。
    """

    scene: Scene
    scene_id: str
    msg_id: str | None
    event_id: str | None
    received_at: datetime


@dataclass(frozen=True, slots=True)
class IncomingMessage:
    """一条归一化后的消息。

    Attributes:
        scene: 场景。
        scene_id: 会话标识。群聊为 ``group_openid``，单聊为 ``user_openid``。
        sender_id: 发送者标识。群聊为 ``member_openid``，单聊为 ``user_openid``。
            两者不互通，不能据此跨场景识别同一用户。
        content: 已清理的正文。
        message_id: 平台消息 ID。
        received_at: 本地收到事件的时刻。
        timestamp: 平台标注的发送时刻，原样保留。与 ``received_at`` 相比即可看出投递
            延迟，平台未提供时为空。
        username: 平台侧昵称。群消息中有值，单聊中为空串。
        role: 发送者在群内的身份，单聊与未知取值时为空。
        addressed_to_others: 消息 @ 了他人且其中不含机器人自己。群里可能同时存在多个
            机器人，此时这条消息不该由自己响应。
    """

    scene: Scene
    scene_id: str
    sender_id: str
    content: str
    message_id: str
    received_at: datetime
    timestamp: str | None = None
    username: str = ""
    role: MemberRole | None = None
    addressed_to_others: bool = False

    @property
    def reply_target(self) -> ReplyTarget:
        return ReplyTarget(
            scene=self.scene,
            scene_id=self.scene_id,
            msg_id=self.message_id,
            event_id=None,
            received_at=self.received_at,
        )


@dataclass(frozen=True, slots=True)
class IncomingEvent:
    """一个归一化后的非消息事件。

    Attributes:
        type: 事件类型。
        scene: 场景。
        scene_id: 会话标识。
        operator_id: 触发事件的成员标识，平台未提供时为空。
        event_id: 平台事件 ID，被动回复时作为 ``event_id`` 传回。
        received_at: 本地收到事件的时刻。
        data: 原始事件数据，供插件读取归一化未覆盖的字段。
    """

    type: EventType
    scene: Scene
    scene_id: str
    operator_id: str | None
    event_id: str
    received_at: datetime
    data: dict[str, Any]

    @property
    def reply_target(self) -> ReplyTarget:
        return ReplyTarget(
            scene=self.scene,
            scene_id=self.scene_id,
            msg_id=None,
            event_id=self.event_id,
            received_at=self.received_at,
        )


def normalize_message(payload: Payload, *, received_at: datetime) -> IncomingMessage | None:
    """把 payload 转换为消息模型。

    Returns:
        非消息类事件返回 ``None``。
    """

    if payload.t not in _MESSAGE_EVENTS:
        return None

    if payload.t == EventType.C2C_MESSAGE_CREATE:
        c2c = C2CMessage.model_validate(payload.d)

        return IncomingMessage(
            scene=Scene.C2C,
            scene_id=c2c.author.user_openid,
            sender_id=c2c.author.user_openid,
            content=_clean_content(c2c.content),
            message_id=c2c.id,
            received_at=received_at,
            timestamp=c2c.timestamp,
            username=c2c.author.username,
        )

    group = GroupMessage.model_validate(payload.d)

    return IncomingMessage(
        scene=Scene.GROUP,
        scene_id=group.group_openid,
        sender_id=group.author.member_openid,
        content=_clean_content(group.content),
        message_id=group.id,
        received_at=received_at,
        timestamp=group.timestamp,
        username=group.author.username,
        role=_to_role(group.author.member_role),
        addressed_to_others=_addressed_to_others(group.mentions),
    )


def normalize_event(payload: Payload, *, received_at: datetime) -> IncomingEvent | None:
    """把 payload 转换为事件模型。

    Returns:
        消息类事件、未支持的事件类型，或缺少事件 ID 时返回 ``None``——没有事件 ID
        便无法被动回复，处理它没有意义。
    """

    if payload.t is None or payload.t in _MESSAGE_EVENTS:
        return None

    try:
        event_type = EventType(payload.t)
    except ValueError:
        logger.debug("事件类型 {} 未支持，跳过", payload.t)
        return None

    if payload.id is None:
        logger.debug("事件 {} 缺少事件 ID，无法回复，跳过", payload.t)
        return None

    if event_type in _GROUP_ROBOT_EVENTS:
        group = GroupRobotEvent.model_validate(payload.d)
        scene, scene_id, operator_id = Scene.GROUP, group.group_openid, group.op_member_openid
    elif event_type in _FRIEND_EVENTS:
        friend = FriendEvent.model_validate(payload.d)
        scene, scene_id, operator_id = Scene.C2C, friend.openid, friend.openid
    else:
        logger.debug("事件类型 {} 尚未归一化，跳过", event_type)
        return None

    return IncomingEvent(
        type=event_type,
        scene=scene,
        scene_id=scene_id,
        operator_id=operator_id,
        event_id=payload.id,
        received_at=received_at,
        data=payload.d,
    )


def _addressed_to_others(mentions: Sequence[Mention]) -> bool:
    """判断这条消息 @ 的是否只有别人。

    群里可能同时有多个机器人，正文中的 ``<@openid>`` 标记会被一并剥离，仅凭前缀无法区分
    这条指令是发给谁的。全量推送模式下平台给出 ``mentions``，据此即可让每个机器人只应答
    @ 到自己的消息。

    ``is_you`` 全部缺失时返回 ``False``，即无从判断时按发给自己处理，理由见
    :class:`~dicerobot.qq.schemas.Mention`。
    """

    if not any(mention.is_you is not None for mention in mentions):
        return False

    return not any(mention.is_you for mention in mentions)


def _to_role(raw: str | None) -> MemberRole | None:
    """把平台的角色字符串转为枚举。

    未知取值返回空而非按普通成员处理，调用方据此拒绝敏感操作。
    """

    if raw is None:
        return None

    try:
        return MemberRole(raw)
    except ValueError:
        logger.debug("未知的群成员身份 {}", raw)

        return None


def _clean_content(content: str) -> str:
    """剥离 @ 标记并去除首尾空白。

    平台不会替调用方清理正文：@ 机器人的消息正文形如 ``<@openid> .r``，标记与其后的
    空格若不去除，指令前缀便无法匹配。
    """

    return _MENTION_PATTERN.sub("", content).strip()
