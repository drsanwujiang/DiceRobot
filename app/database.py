from sqlalchemy import Enum, create_engine
from sqlalchemy.orm import DeclarativeBase, Mapped, mapped_column, sessionmaker

from .enum import ChatType


engine = create_engine("sqlite:///database.db", connect_args={"check_same_thread": False})
Session = sessionmaker(autocommit=False, autoflush=False, bind=engine)


class Base(DeclarativeBase):
    pass


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


def init_database() -> None:
    engine.connect()
    Base.metadata.create_all(bind=engine)


def clean_database() -> None:
    engine.dispose()
