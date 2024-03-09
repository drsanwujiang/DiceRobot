import json
import secrets

from sqlalchemy import select
from sqlalchemy.dialects.sqlite import insert

from .version import VERSION
from .log import logger
from .database import Session
from .models import Settings, PluginSettings, Replies, ChatSettings
from .internal.enum import AppStatus, ChatType


class Config(dict):
    def __init__(self, d: dict = None):
        super().__init__()

        d = {} if d is None else d

        for key, value in d.items():
            self[key] = value

    def __setitem__(self, key, value):
        if isinstance(value, dict):
            value = Config(value)

        super().__setitem__(key, value)

    def __missing__(self, _):
        return None

    def update(self, d: dict) -> "Config":
        for key, value in d.items():
            if isinstance(value, dict):
                if key not in self:
                    self[key] = Config()

                self[key].update(value)
            else:
                self[key] = value

        return self

    def setdefault(self, key, default):
        if key not in self:
            self[key] = default

        return self[key]


status: Config[str] = Config({
    "app": AppStatus.INITIALIZING,
    "version": VERSION,
    "report": {
        "order": True,
        "event": True
    },
    "bot": {
        "id": -1,
        "nickname": ""
    }
})
settings: Config[str, Config] = Config({
    "security": {
        "webhook": {
            "token": secrets.token_urlsafe(32)
        },
        "jwt": {
            "secret": secrets.token_urlsafe(32),
            "algorithm": "HS256"
        }
    },
    "mirai": {
        "api": {
            "base_url": "http://127.0.0.1:9000"
        }
    }
})
plugin_settings: Config[str, Config] = Config()
replies: Config[str, Config[str, str]] = Config({
    "dicerobot": ({
        "network_client_error": "致远星拒绝了我们的请求……请稍后再试",
        "network_server_error": "糟糕，致远星出错了……请稍后再试",
        "network_invalid_content": "致远星返回了无法解析的内容……请稍后再试",
        "network_error": "无法连接到致远星，请检查星际通讯是否正常",
        "order_invalid": "不太理解这个指令呢……",
    })
})
chat_settings: Config[str, Config[int, Config]] = Config({
    ChatType.FRIEND.value: {},
    ChatType.GROUP.value: {}
})


def init_config() -> None:
    logger.info("Initializing config")

    with Session() as session, session.begin():
        for item in session.execute(select(Settings)).scalars().fetchall():  # type: Settings
            try:
                settings.update({
                    item.group: json.loads(item.json)
                })
            except json.JSONDecodeError:
                logger.error(f"Failed to load settings, group: {item.group}")
                continue

        for item in session.execute(select(PluginSettings)).scalars().fetchall():  # type: PluginSettings
            try:
                plugin_settings.update({
                    item.plugin: json.loads(item.json)
                })
            except json.JSONDecodeError:
                logger.error(f"Failed to load plugin settings, plugin: {item.plugin}")
                continue

        for item in session.execute(select(Replies)).scalars().fetchall():  # type: Replies
            replies.update({
                item.group: {
                    item.key: item.value
                }
            })

        for item in session.execute(select(ChatSettings)).scalars().fetchall():  # type: ChatSettings
            try:
                chat_settings.update({
                    item.chat_type: {
                        item.chat_id: {
                            item.group: json.loads(item.json)
                        }
                    }
                })
            except json.JSONDecodeError:
                logger.error(f"Failed to load chat settings, chat: {item.chat_type.value} {item.chat_id}, group: {item.group})")
                continue

    logger.info("Config initialized")


def save_config() -> None:
    logger.info("Saving config")

    with Session() as session, session.begin():
        for key, value in settings.items():
            try:
                serialized = json.dumps(value)
            except TypeError:
                logger.error(f"Failed to save settings, group: {key}")
                continue

            session.execute(
                insert(Settings).values(
                    group=key,
                    json=serialized
                ).on_conflict_do_update(
                    index_elements=["group"],
                    set_={"json": serialized}
                )
            )

        for key, value in plugin_settings.items():
            try:
                serialized = json.dumps(value)
            except TypeError:
                logger.error(f"Failed to save plugin settings, plugin: {key}")
                continue

            session.execute(
                insert(PluginSettings).values(
                    plugin=key,
                    json=serialized
                ).on_conflict_do_update(
                    index_elements=["plugin"],
                    set_={"json": serialized}
                )
            )

        for group, group_replies in replies.items():
            for key, value in group_replies.items():
                session.execute(insert(Replies).values(
                    group=group,
                    key=key,
                    value=value
                ).on_conflict_do_update(
                    index_elements=["group", "key"],
                    set_={"value": value})
                )

        for chat_type, chat_type_settings in chat_settings.items():
            for chat_id, chat_id_settings in chat_type_settings.items():
                for key, value in chat_id_settings.items():
                    try:
                        serialized = json.dumps(value)
                    except TypeError:
                        logger.error(f"Failed to save chat settings, chat: {chat_type.value} {chat_id}, group: {key})")
                        continue

                    session.execute(
                        insert(ChatSettings).values(
                            chat_type=chat_type,
                            chat_id=chat_id,
                            group=key,
                            json=serialized
                        ).on_conflict_do_update(
                            index_elements=["chat_type", "chat_id", "group"],
                            set_={"json": serialized}
                        )
                    )

    logger.info("Config saved")
