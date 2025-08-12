from typing import Any
import os
import secrets
from ipaddress import IPv4Address
from copy import deepcopy

from pydantic import Field, field_serializer
from werkzeug.security import generate_password_hash

from ..globals import VERSION, LOG_DIR
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

    debug: bool = Field(default=False, exclude=True)
    version: str = VERSION
    app: ApplicationStatus = ApplicationStatus.STARTED
    module: Module = Module()
    plugins: dict[str, Plugin] = Field(default={}, exclude=True)
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
            cloud: DiceRobot cloud settings.
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
                expiration: int = 3600 * 24

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
                dir: Application directory settings.
            """

            class Directory(BaseModel):
                """Application directory settings.

                Attributes:
                    base: Base directory of the application.
                    logs: Logs directory of the application.
                    data: Data directory of the application.
                    temp: Temporary directory of the application.
                """

                base: str = os.getcwd()
                logs: str = LOG_DIR
                data: str = os.path.join(base, "data")
                temp: str = os.path.join(base, "temp")

            dir: Directory = Directory()

        class Cloud(BaseModel):
            """DiceRobot cloud settings.

            Attributes:
                api: DiceRobot cloud API settings.
                download: DiceRobot cloud download settings.
            """

            class API(BaseModel):
                """DiceRobot cloud API settings.

                Attributes:
                    base_url: DiceRobot cloud API base URL.
                """
                base_url: str = "https://api.dicerobot.tech"

            class Download(BaseModel):
                """DiceRobot cloud download settings.

                Attributes:
                    base_url: DiceRobot cloud download base URL.
                """
                base_url: str = "https://download.dicerobot.tech"

            api: API = API()
            download: Download = Download()

        class QQ(BaseModel):
            """QQ settings.

            Attributes:
                dir: QQ directory settings.
            """

            class Directory(BaseModel):
                """QQ directory settings.

                Attributes:
                    base: Base directory of QQ.
                    config: Configuration directory of QQ.
                """

                base: str = "/opt/QQ"
                config: str = "/root/.config/QQ"

            dir: Directory = Directory()

        class NapCat(BaseModel):
            """NapCat settings.

            Attributes:
                dir: NapCat directory settings.
                api: NapCat API settings.
                account: QQ account.
                autostart: Whether to start NapCat at application startup.
            """

            class Directory(BaseModel):
                """NapCat directory settings.

                Attributes:
                    base: Base directory of NapCat.
                    config: Configuration directory of NapCat.
                    logs: Logs directory of NapCat.
                """

                base: str = "/opt/QQ/resources/app/app_launcher/napcat"
                config: str = os.path.join(base, "config")
                logs: str = os.path.join(base, "logs")

            class API(BaseModel):
                """NapCat API settings.

                Attributes:
                    host: OneBot HTTP host.
                    port: OneBot HTTP port.
                """

                host: IPv4Address = IPv4Address("127.0.0.1")
                port: int = Field(default=13579, gt=0)

                @field_serializer("host")
                def serialize_host(self, host: IPv4Address) -> str:
                    return str(host)

                # noinspection HttpUrlsUsage
                @property
                def base_url(self) -> str:
                    return f"http://{self.host}:{self.port}"

            dir: Directory = Directory()
            api: API = API()
            account: int = -1
            autostart: bool = False

        security: Security = Security()
        app: Application = Application()
        cloud: Cloud = Cloud()
        qq: QQ = QQ()
        napcat: NapCat = NapCat()

    def __init__(self) -> None:
        self._settings = self._Settings()

    def update(self, settings: dict) -> None:
        """Update all the settings.

        Args:
            settings: New settings.
        """

        self._settings = self._Settings.model_validate(settings)

    def update_security(self, settings: dict) -> None:
        """Update security settings.

        Args:
            settings: New security settings.
        """

        security_settings = self._settings.security.model_dump()

        if "webhook" in settings:
            security_settings["webhook"] = deep_update(security_settings["webhook"], settings["webhook"])
        if "jwt" in settings:
            security_settings["jwt"] = deep_update(security_settings["jwt"], settings["jwt"])
        if "admin" in settings:
            security_settings["admin"]["password_hash"] = generate_password_hash(settings["admin"]["password"])

        self._settings.security = self._Settings.Security.model_validate(security_settings)

    def update_application(self, settings: dict) -> None:
        """Update application settings.

        Args:
            settings: New application settings.
        """

        self._settings.app = self._Settings.Application.model_validate(
            deep_update(self._settings.app.model_dump(), settings)
        )

    def update_qq(self, settings: dict) -> None:
        """Update QQ settings.

        Args:
            settings: New QQ settings.
        """

        self._settings.qq = self._Settings.QQ.model_validate(
            deep_update(self._settings.qq.model_dump(), settings)
        )

    def update_napcat(self, settings: dict) -> None:
        """Update NapCat settings.

        Args:
            settings: New NapCat settings.
        """

        self._settings.napcat = self._Settings.NapCat.model_validate(
            deep_update(self._settings.napcat.model_dump(), settings)
        )

    def model_dump(self, safe_dump: bool = True, **kwargs) -> dict:
        data = self._settings.model_dump(**kwargs)

        if safe_dump:
            del data["security"]  # For security, sensitive data should be excluded

        return data

    def __getattr__(self, item) -> Any:
        """Get attribute from inner actual settings class."""

        return getattr(self._settings, item)


class PluginSettings:
    """DiceRobot plugin settings."""

    def __init__(self) -> None:
        self._settings: dict[str, dict] = {}

    def get(self, *, plugin: str) -> dict:
        """Get settings of a plugin.

        Args:
            plugin: Plugin name.

        Returns:
            A deep copy of the settings for preventing modification.
        """

        return deepcopy(self._settings.setdefault(plugin, {}))

    def set(self, *, plugin: str, settings: dict) -> None:
        """Set settings of a plugin.

        Args:
            plugin: Plugin name.
            settings: Settings to be set.
        """

        if plugin in self._settings:
            self._settings[plugin] |= deepcopy(settings)
        else:
            self._settings[plugin] = deepcopy(settings)

    def model_dump(self) -> dict:
        """Dump all plugin settings.

        Returns:
            A deep copy of all plugin settings.
        """

        return deepcopy(self._settings)


class ChatSettings:
    """DiceRobot chat settings."""

    def __init__(self) -> None:
        self._settings: dict[ChatType, dict[int, dict[str, dict]]] = {
            ChatType.FRIEND: {},
            ChatType.GROUP: {},
            ChatType.TEMP: {}
        }

    def get(self, *, chat_type: ChatType, chat_id: int, settings_group: str) -> dict:
        """Get settings of a chat.

        Args:
            chat_type: Chat type.
            chat_id: Chat ID.
            settings_group: Settings group.

        Returns:
            Settings of the chat.
        """

        return self._settings[chat_type].setdefault(chat_id, {}).setdefault(settings_group, {})

    def set(self, *, chat_type: ChatType, chat_id: int, settings_group: str, settings: dict) -> None:
        """Set settings of a chat.

        Args:
            chat_type: Chat type.
            chat_id: Chat ID.
            settings_group: Settings group.
            settings: Settings to be set.
        """

        if settings_group in self._settings[chat_type].setdefault(chat_id, {}):
            self._settings[chat_type][chat_id][settings_group] |= deepcopy(settings)
        else:
            self._settings[chat_type][chat_id][settings_group] = deepcopy(settings)

    def model_dump(self) -> dict:
        """Dump all chat settings.

        Returns:
            A deep copy of all chat settings.
        """

        return deepcopy(self._settings)


class Replies:
    """DiceRobot plugin replies."""

    def __init__(self) -> None:
        self._replies: dict[str, dict[str, str]] = {
            "dicerobot": {
                "network_client_error": "致远星拒绝了我们的请求……请稍后再试",
                "network_server_error": "糟糕，致远星出错了……请稍后再试",
                "network_invalid_content": "致远星返回了无法解析的内容……请稍后再试",
                "network_error": "无法连接到致远星，请检查星际通讯是否正常",
                "order_invalid": "不太理解这个指令呢……",
                "order_suspicious": "唔……这个指令有点问题……",
                "order_repetition_exceeded": "这条指令不可以执行这么多次哦~",
            }
        }

    def get_replies(self, *, group: str) -> dict:
        """Get replies of a group.

        Args:
            group: Reply group, usually the name of the plugin.

        Returns:
            A deep copy of the replies for preventing modification.
        """

        return deepcopy(self._replies.setdefault(group, {}))

    def get_reply(self, *, group: str, key: str) -> str:
        """Get a reply of a group.

        Args:
            group: Reply group, usually the name of the plugin.
            key: Reply key.

        Returns:
            The reply.
        """

        return self._replies[group][key]

    def set_replies(self, *, group: str, replies: dict) -> None:
        """Set replies of a group.

        Args:
            group: Reply group, usually the name of the plugin.
            replies: Replies to be set.
        """

        if group in self._replies:
            self._replies[group] |= deepcopy(replies)
        else:
            self._replies[group] = deepcopy(replies)

    def model_dump(self) -> dict:
        """Dump all plugin replies.

        Returns:
            A deep copy of all plugin replies.
        """

        return deepcopy(self._replies)
