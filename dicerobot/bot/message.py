"""把平台事件归一化为与场景无关的模型。

群聊与单聊在平台侧字段不同，全量群消息与 @ 消息又是两个事件类型。归一化后，路由、
配额与插件实现只需面对一种形状。

消息与非消息事件都可以被动回复：前者携带 ``msg_id``，后者携带 ``event_id``。两者
通过 :class:`ReplyTarget` 统一，回复配额的计量因此不必区分来源。
"""

from __future__ import annotations

import re
from dataclasses import dataclass
from datetime import datetime
from typing import Any

from loguru import logger

from dicerobot.enums import Scene
from dicerobot.qq.enums import EventType
from dicerobot.qq.schemas import C2CMessage, FriendEvent, GroupMessage, GroupRobotEvent, Payload

__all__ = ["IncomingEvent", "IncomingMessage", "ReplyTarget", "normalize_event", "normalize_message"]

# 机器人自身的 @ 已被平台剥离，但正文中可能残留其他成员的 @ 标记。
_MENTION_PATTERN = re.compile(r"<@!?\d+>")

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
    """

    scene: Scene
    scene_id: str
    sender_id: str
    content: str
    message_id: str
    received_at: datetime

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
        )

    group = GroupMessage.model_validate(payload.d)

    return IncomingMessage(
        scene=Scene.GROUP,
        scene_id=group.group_openid,
        sender_id=group.author.member_openid,
        content=_clean_content(group.content),
        message_id=group.id,
        received_at=received_at,
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


def _clean_content(content: str) -> str:
    """剥离残留的 @ 标记并去除首尾空白。

    @ 机器人的消息正文以空格开头，不清理则指令前缀无法匹配。
    """

    return _MENTION_PATTERN.sub("", content).strip()
