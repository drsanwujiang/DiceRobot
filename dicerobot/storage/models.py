"""持久化模型。

平台只提供不透明的 openid，且群内标识与单聊标识互不相通，因此会话与成员均以
``(scene, openid)`` 为主键，不存在跨场景的统一用户身份。
"""

from __future__ import annotations

from datetime import datetime
from typing import Any

from sqlalchemy import JSON, DateTime, Enum, ForeignKeyConstraint, String, func
from sqlalchemy.orm import DeclarativeBase, Mapped, mapped_column

from dicerobot.enums import Scene

__all__ = ["Base", "Chat", "ChatPluginState", "Member", "PluginState"]

_PLUGIN_NAME_LENGTH = 64

# 平台的 openid 为定长十六进制串，64 位留有充足余量。
_OPENID_LENGTH = 64

_SCENE_COLUMN = Enum(Scene, values_callable=lambda enum: [member.value for member in enum])


class Base(DeclarativeBase):
    """全部模型的基类。"""


class TimestampMixin:
    """创建与更新时间。由数据库生成，避免依赖应用侧时钟。"""

    created_at: Mapped[datetime] = mapped_column(DateTime(timezone=True), server_default=func.now())
    updated_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True),
        server_default=func.now(),
        onupdate=func.now(),
    )


class Chat(Base, TimestampMixin):
    """一个会话，即一个群或一条单聊。"""

    __tablename__ = "chats"

    scene: Mapped[Scene] = mapped_column(_SCENE_COLUMN, primary_key=True)
    openid: Mapped[str] = mapped_column(String(_OPENID_LENGTH), primary_key=True)

    enabled: Mapped[bool] = mapped_column(default=True)
    """机器人是否在本会话中响应指令。插件各自的开关另见 :class:`ChatPluginState`。"""


class Member(Base, TimestampMixin):
    """会话中的一位成员。

    单聊场景下 ``openid`` 与所属会话的 ``openid`` 相同，为保持模型统一仍单独存储。
    """

    __tablename__ = "members"
    __table_args__ = (
        ForeignKeyConstraint(
            ["scene", "chat_openid"],
            ["chats.scene", "chats.openid"],
            ondelete="CASCADE",
        ),
    )

    scene: Mapped[Scene] = mapped_column(_SCENE_COLUMN, primary_key=True)
    chat_openid: Mapped[str] = mapped_column(String(_OPENID_LENGTH), primary_key=True)
    openid: Mapped[str] = mapped_column(String(_OPENID_LENGTH), primary_key=True)

    nickname: Mapped[str | None] = mapped_column(String(32), default=None)
    """自行设置的昵称。平台不提供昵称，此字段是唯一的人类可读标识来源。"""


class PluginState(Base, TimestampMixin):
    """插件的全局状态。

    设置存为 JSON 而非独立的列：插件的设置项各不相同，且第三方插件无法为自己新增
    列。结构由插件声明的 pydantic 模型在读取时校验并补齐默认值。
    """

    __tablename__ = "plugin_states"

    plugin: Mapped[str] = mapped_column(String(_PLUGIN_NAME_LENGTH), primary_key=True)

    enabled: Mapped[bool] = mapped_column(default=True)
    settings: Mapped[dict[str, Any]] = mapped_column(JSON, default=dict)


class ChatPluginState(Base, TimestampMixin):
    """插件在某个会话中的状态。"""

    __tablename__ = "chat_plugin_states"
    __table_args__ = (
        ForeignKeyConstraint(
            ["scene", "chat_openid"],
            ["chats.scene", "chats.openid"],
            ondelete="CASCADE",
        ),
    )

    scene: Mapped[Scene] = mapped_column(_SCENE_COLUMN, primary_key=True)
    chat_openid: Mapped[str] = mapped_column(String(_OPENID_LENGTH), primary_key=True)
    plugin: Mapped[str] = mapped_column(String(_PLUGIN_NAME_LENGTH), primary_key=True)

    enabled: Mapped[bool] = mapped_column(default=True)
    settings: Mapped[dict[str, Any]] = mapped_column(JSON, default=dict)
