from sqlalchemy import Enum
from sqlalchemy.orm import DeclarativeBase, Mapped, mapped_column

from .enum import ChatType

__all__ = [
    "Base",
    "Settings",
    "PluginSettings",
    "Replies",
    "ChatSettings"
]


class Base(DeclarativeBase):
    ...


class Settings(Base):
    __tablename__ = "settings"

    group: Mapped[str] = mapped_column(primary_key=True, nullable=False)
    json: Mapped[str] = mapped_column(nullable=False)


class PluginSettings(Base):
    __tablename__ = "plugin_settings"

    plugin: Mapped[str] = mapped_column(primary_key=True, nullable=False)
    json: Mapped[str] = mapped_column(nullable=False)


class Replies(Base):
    __tablename__ = "replies"

    group: Mapped[str] = mapped_column(primary_key=True, nullable=False)
    key: Mapped[str] = mapped_column(primary_key=True, nullable=False)
    value: Mapped[str] = mapped_column(nullable=False)


class ChatSettings(Base):
    __tablename__ = "chat_settings"

    chat_type: Mapped[ChatType] = mapped_column(Enum(ChatType, values_callable=lambda x: [e.value for e in x]), primary_key=True, nullable=False)
    chat_id: Mapped[int] = mapped_column(primary_key=True, nullable=False)
    group: Mapped[str] = mapped_column(primary_key=True, nullable=False)
    json: Mapped[str] = mapped_column(nullable=False)
