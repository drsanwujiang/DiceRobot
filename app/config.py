import os
import json

from loguru import logger
from sqlalchemy import select
from sqlalchemy.dialects.sqlite import insert

from .models.config import (
    Status as StatusModel, Settings as SettingsModel, PluginSettings as PluginSettingsModel,
    ChatSettings as ChatSettingsModel, Replies as RepliesModel
)
from .database import Session, Settings, PluginSettings, Replies, ChatSettings
from .enum import ChatType

status = StatusModel(debug=os.environ.get("DICEROBOT_DEBUG") is not None)
settings = SettingsModel()
plugin_settings = PluginSettingsModel()
chat_settings = ChatSettingsModel()
replies = RepliesModel()


def load_config() -> None:
    with Session() as session, session.begin():
        # Settings
        _settings = {}

        for item in session.execute(select(Settings)).scalars().fetchall():  # type: Settings
            _settings[item.group] = json.loads(item.json)

        settings.update(_settings)

        # Plugin settings
        _plugin_settings = {}

        for item in session.execute(select(PluginSettings)).scalars().fetchall():  # type: PluginSettings
            _plugin_settings[item.plugin] = json.loads(item.json)

        for _plugin, _settings in _plugin_settings.items():
            plugin_settings.set(plugin=_plugin, settings=_settings)

        # Chat settings
        _chat_settings = {}

        for item in session.execute(select(ChatSettings)).scalars().fetchall():  # type: ChatSettings
            _chat_settings.setdefault(ChatType(item.chat_type), {}).setdefault(item.chat_id, {})[item.group] = json.loads(item.json)

        for _chat_type, _chat_type_settings in _chat_settings.items():
            for _chat_id, _chat_id_settings in _chat_type_settings.items():
                for _setting_group, _settings in _chat_id_settings.items():
                    chat_settings.set(chat_type=_chat_type, chat_id=_chat_id, setting_group=_setting_group, settings=_settings)

        # Replies
        _replies = {}

        for item in session.execute(select(Replies)).scalars().fetchall():  # type: Replies
            _replies.setdefault(item.group, {})[item.key] = item.value

        for _group, _group_replies in _replies.items():
            replies.set_replies(group=_group, replies=_group_replies)

    logger.debug("Configuration loaded")


def save_config() -> None:
    logger.info("Save config")

    with Session() as session, session.begin():
        for key, value in settings.model_dump(safe_dump=False).items():  # type: str, dict
            serialized = json.dumps(value)
            session.execute(
                insert(Settings)
                .values(group=key, json=serialized)
                .on_conflict_do_update(index_elements=["group"], set_={"json": serialized})
            )

        for key, value in plugin_settings.dict().items():  # type: str, dict
            serialized = json.dumps(value)
            session.execute(
                insert(PluginSettings)
                .values(plugin=key, json=serialized)
                .on_conflict_do_update(index_elements=["plugin"], set_={"json": serialized})
            )

        for chat_type, _settings in chat_settings.dict().items():  # type: ChatType, dict[int, dict[str, dict]]
            for chat_id, _chat_settings in _settings.items():  # type: int, dict[str, dict]
                for key, value in _chat_settings.items():  # type: str, dict
                    serialized = json.dumps(value)
                    session.execute(
                        insert(ChatSettings)
                        .values(chat_type=chat_type.value, chat_id=chat_id, group=key, json=serialized)
                        .on_conflict_do_update(index_elements=["chat_type", "chat_id", "group"], set_={"json": serialized})
                    )

        for group, group_replies in replies.dict().items():  # type: str, dict[str, str]
            for key, value in group_replies.items():  # type: str, str
                session.execute(insert(Replies).values(
                    group=group,
                    key=key,
                    value=value
                ).on_conflict_do_update(
                    index_elements=["group", "key"],
                    set_={"value": value})
                )
