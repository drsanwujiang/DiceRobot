from typing import Any
import secrets
from copy import deepcopy

from pydantic import Field, field_validator
from werkzeug.security import generate_password_hash

from ..version import VERSION
from ..enum import ApplicationStatus, ChatType
from . import BaseModel


class Status(BaseModel):
    """DiceRobot status.

    Attributes:
        version: Version of DiceRobot.
        app: Application status.
        module: Module status.
        plugins: Loaded plugin list.
        bot: Bot information.
    """

    class Module(BaseModel):
        """DiceRobot module status.

        Attributes:
            order: Whether order module (all order plugins) is enabled.
            event: Whether event module (all event plugins) is enabled.
        """

        order: bool = True
        event: bool = True

    class Plugin(BaseModel):
        """Plugin information.

        Attributes:
            display_name: Display name.
            description: Description.
            version: Version.
        """

        display_name: str
        description: str
        version: str

    class Bot(BaseModel):
        """Bot information.

        Attributes:
            id: Bot ID.
            nickname: Bot nickname.
            friends: Friend list.
            groups: Group list.
        """

        id: int = -1
        nickname: str = ""
        friends: list[int] = []
        groups: list[int] = []

    version: str = VERSION
    app: ApplicationStatus = ApplicationStatus.STARTED
    module: Module = Module()
    plugins: dict[str, Plugin] = {}
    bot: Bot = Bot()


class Settings:
    """DiceRobot settings.

    This class uses a inner class to store actual settings, so that the settings can be updated by `update` method.
    """

    class _Settings(BaseModel):
        """Actual DiceRobot settings class.

        Attributes:
            security: Security settings.
            mirai: Mirai settings.
        """

        class Security(BaseModel):
            """Security settings.

            Attributes:
                webhook: Webhook settings.
                jwt: JWT settings.
                admin: Administration settings.
            """

            class Webhook(BaseModel):
                """Webhook settings.

                Attributes:
                    token: Webhook token.
                """

                token: str = secrets.token_urlsafe(32)

            class JWT(BaseModel):
                """JWT settings.

                Attributes:
                    secret: JWT secret.
                    algorithm: JWT algorithm.
                """

                secret: str = secrets.token_urlsafe(32)
                algorithm: str = "HS256"

            class Admin(BaseModel):
                """Administration settings.

                Attributes:
                    password: Administrator password. For security, this field will be excluded when serialization.
                """

                password: str = Field("", exclude=True)

                @field_validator("password", mode="after")
                def process_password(cls, value: str) -> str:
                    """Process password field.

                    Args:
                        value: Raw password.

                    Returns:
                        Password hash.
                    """

                    return generate_password_hash(value)

            webhook: Webhook = Webhook()
            jwt: JWT = JWT()
            admin: Admin = Admin()

        class Mirai(BaseModel):
            """Mirai settings.

            Attributes:
                api: Mirai API HTTP settings.
            """

            class API(BaseModel):
                """Mirai API HTTP settings.

                Attributes:
                    base_url: Mirai API HTTP base URL.
                """

                base_url: str = "http://127.0.0.1:9000"

            api: API = API()

        security: Security = Security()
        mirai: Mirai = Mirai()

    _settings: _Settings = _Settings()

    @classmethod
    def update(cls, settings: dict) -> None:
        """Update all the settings.

        Args:
            settings: New settings.
        """

        cls._settings = cls._Settings.model_validate(settings)

    def __getattr__(self, item) -> Any:
        """Get attribute from inner actual settings class."""

        return getattr(self._settings, item)


class PluginSettings:
    """DiceRobot plugin settings."""

    _plugin_settings: dict[str, dict] = {}

    @classmethod
    def get(cls, *, plugin: str) -> dict:
        """Get settings of a plugin.

        Args:
            plugin: Plugin name.

        Returns:
            A deep copy of the settings of the plugin, for preventing modification.
        """

        return deepcopy(cls._plugin_settings.setdefault(plugin, {}))

    @classmethod
    def set(cls, *, plugin: str, settings: dict) -> None:
        """Set settings of a plugin.

        Args:
            plugin: Plugin name.
            settings: Settings to be set.
        """

        cls._plugin_settings[plugin] = deepcopy(settings)

    @classmethod
    def dict(cls) -> dict:
        """Get all plugin settings.

        Returns:
            A deep copy of all plugin settings.
        """

        return deepcopy(cls._plugin_settings)


class ChatSettings:
    """DiceRobot chat settings."""

    _chat_settings: dict[ChatType, dict[int, dict[str, dict]]] = {
        ChatType.FRIEND: {},
        ChatType.GROUP: {},
        ChatType.TEMP: {}
    }

    @classmethod
    def get(cls, *, chat_type: ChatType, chat_id: int, setting_group: str) -> dict:
        """Get settings of a chat.

        Args:
            chat_type: Chat type.
            chat_id: Chat ID.
            setting_group: Setting group.

        Returns:
            Settings of the chat.
        """

        return cls._chat_settings[chat_type].setdefault(chat_id, {}).setdefault(setting_group, {})

    @classmethod
    def set(cls, *, chat_type: ChatType, chat_id: int, setting_group: str, settings: dict) -> None:
        """Set settings of a chat.

        Args:
            chat_type: Chat type.
            chat_id: Chat ID.
            setting_group: Setting group.
            settings: Settings to be set.
        """
        cls._chat_settings[chat_type].setdefault(chat_id, {})[setting_group] = deepcopy(settings)

    @classmethod
    def dict(cls) -> dict:
        """Get all chat settings.

        Returns:
            A deep copy of all chat settings.
        """
        return deepcopy(cls._chat_settings)


class Replies:
    """DiceRobot plugin replies."""

    _replies: dict[str, dict[str, str]] = {
        "dicerobot": {
            "network_client_error": "致远星拒绝了我们的请求……请稍后再试",
            "network_server_error": "糟糕，致远星出错了……请稍后再试",
            "network_invalid_content": "致远星返回了无法解析的内容……请稍后再试",
            "network_error": "无法连接到致远星，请检查星际通讯是否正常",
            "order_invalid": "不太理解这个指令呢……"
        }
    }

    @classmethod
    def get(cls, *, reply_group: str) -> dict:
        """Get replies of a plugin.

        Args:
            reply_group: Reply group, usually the name of the plugin.

        Returns:
            A deep copy of the replies of the reply group, for preventing modification.
        """

        return deepcopy(cls._replies.setdefault(reply_group, {}))

    @classmethod
    def get_reply(cls, *, reply_group: str, reply_key: str) -> str:
        """Get a reply of a plugin.

        Args:
            reply_group: Reply group, usually the name of the plugin.
            reply_key: Reply key.

        Returns:
            The reply.
        """
        return cls._replies[reply_group][reply_key]

    @classmethod
    def set(cls, *, reply_group: str, replies: dict) -> None:
        """Set replies of a plugin.

        Args:
            reply_group: Reply group, usually the name of the plugin.
            replies: Replies to be set.
        """

        cls._replies[reply_group] = deepcopy(replies)

    @classmethod
    def dict(cls) -> dict:
        """Get all plugin replies.

        Returns:
            A deep copy of all plugin replies.
        """

        return deepcopy(cls._replies)
