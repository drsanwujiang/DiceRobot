import json

from sqlalchemy import select
from sqlalchemy.dialects.sqlite import insert

from .log import logger
from .models.config import (
    Status as StatusModel, Settings as SettingsModel, PluginSettings as PluginSettingsModel,
    ChatSettings as ChatSettingsModel, Replies as RepliesModel
)
from .database import Session, Settings, PluginSettings, Replies, ChatSettings
from .enum import ChatType

status = StatusModel()
settings = SettingsModel()
plugin_settings = PluginSettingsModel()
chat_settings = ChatSettingsModel()
replies = RepliesModel()


def init_config() -> None:
    with Session() as session, session.begin():
        # Settings
        _settings = {}

        for item in session.execute(select(Settings)).scalars().fetchall():  # type: Settings
            _settings.update({item.group: json.loads(item.json)})

        settings.update(_settings)

        # Plugin settings
        _plugin_settings = {}

        for item in session.execute(select(PluginSettings)).scalars().fetchall():  # type: PluginSettings
            _plugin_settings.update({item.plugin: json.loads(item.json)})

        for _plugin, _settings in _plugin_settings.items():
            plugin_settings.set(plugin=_plugin, settings=_settings)

        # Chat settings
        _chat_settings = {}

        for item in session.execute(select(ChatSettings)).scalars().fetchall():  # type: ChatSettings
            _chat_settings.update({ChatType(item.chat_type): {item.chat_id: {item.group: json.loads(item.json)}}})

        for _chat_type, _chat_type_settings in _chat_settings.items():
            for _chat_id, _chat_id_settings in _chat_type_settings.items():
                for _setting_group, _settings in _chat_id_settings.items():
                    chat_settings.set(chat_type=_chat_type, chat_id=_chat_id, setting_group=_setting_group, settings=_settings)

        # Replies
        _replies = {}

        for item in session.execute(select(Replies)).scalars().fetchall():  # type: Replies
            _replies.update({item.group: {item.key: item.value}})

        for _reply_group, _group_replies in _replies.items():
            replies.set(reply_group=_reply_group, replies=_group_replies)

    logger.info("Config initialized")


def save_config() -> None:
    logger.info("Saving config")

    with Session() as session, session.begin():
        for key, value in settings.model_dump().items():  # type: str, dict
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
