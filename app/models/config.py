from typing import Any
import secrets
from ipaddress import IPv4Address
from copy import deepcopy

from pydantic import Field, field_serializer
from werkzeug.security import generate_password_hash

from ..version import VERSION
from ..enum import ApplicationStatus, ChatType
from ..utils import deep_update
from . import BaseModel

__all__ = [
    "Status",
    "Settings",
    "PluginSettings",
    "ChatSettings",
    "Replies"
]


class Status(BaseModel):
    """DiceRobot status.

    Attributes:
        debug: Whether debug mode is enabled.
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

    debug: bool = Field(False, exclude=True)
    version: str = VERSION
    app: ApplicationStatus = ApplicationStatus.STARTED
    module: Module = Module()
    plugins: dict[str, Plugin] = Field({}, exclude=True)
    bot: Bot = Bot()


class Settings:
    """DiceRobot settings.

    This class uses an inner class to store actual settings, so that the settings can be updated by `update` method.
    """

    class _Settings(BaseModel):
        """Actual DiceRobot settings class.

        Attributes:
            security: Security settings.
            app: Application settings.
            napcat: NapCat settings.
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
                    secret: Webhook secret.
                """

                secret: str = secrets.token_urlsafe(32)

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
                    password_hash: Administrator password hash.
                """

                password_hash: str = ""

            webhook: Webhook = Webhook()
            jwt: JWT = JWT()
            admin: Admin = Admin()

        class Application(BaseModel):
            """Application settings.

            Attributes:
                start_napcat_at_startup: Whether to start NapCat at startup.
            """

            start_napcat_at_startup: bool = False

        class NapCat(BaseModel):
            """NapCat settings.

            Attributes:
                api: NapCat API settings.
                account: QQ account.
            """

            class API(BaseModel):
                """NapCat API settings.

                Attributes:
                    host: OneBot HTTP host.
                    port: OneBot HTTP port.
                """

                host: IPv4Address = IPv4Address("127.0.0.1")
                port: int = Field(13579, gt=0)

                @field_serializer("host")
                def serialize_host(self, host: IPv4Address, _) -> str:
                    return str(host)

                @property
                def base_url(self) -> str:
                    return f"http://{self.host}:{self.port}"

            api: API = API()
            account: int = -1

        security: Security = Security()
        app: Application = Application()
        napcat: NapCat = NapCat()

    _settings: _Settings = _Settings()

    @classmethod
    def update(cls, settings: dict) -> None:
        """Update all the settings.

        Args:
            settings: New settings.
        """

        cls._settings = cls._Settings.model_validate(settings)

    @classmethod
    def update_security(cls, settings: dict) -> None:
        """Update security settings.

        Args:
            settings: New security settings.
        """

        security_settings = cls._settings.security.model_dump()

        if "webhook" in settings:
            security_settings["webhook"] = deep_update(security_settings["webhook"], settings["webhook"])
        if "jwt" in settings:
            security_settings["jwt"] = deep_update(security_settings["jwt"], settings["jwt"])
        if "admin" in settings:
            security_settings["admin"]["password_hash"] = generate_password_hash(settings["admin"]["password"])

        cls._settings.security = cls._Settings.Security.model_validate(security_settings)

    @classmethod
    def update_application(cls, settings: dict) -> None:
        """Update application settings.

        Args:
            settings: New application settings.
        """

        cls._settings.app = cls._Settings.Application.model_validate(
            deep_update(cls._settings.app.model_dump(), settings)
        )

    @classmethod
    def update_napcat(cls, settings: dict) -> None:
        """Update NapCat settings.

        Args:
            settings: New NapCat settings.
        """

        cls._settings.napcat = cls._Settings.NapCat.model_validate(
            deep_update(cls._settings.napcat.model_dump(), settings)
        )

    @classmethod
    def model_dump(cls, safe_dump: bool = True, **kwargs) -> dict:
        data = cls._settings.model_dump(**kwargs)

        if safe_dump:
            del data["security"]  # For security, sensitive data should be excluded

        return data

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
            "order_invalid": "不太理解这个指令呢……",
            "order_suspicious": "唔……这个指令有点问题……"
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
