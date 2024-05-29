import secrets
from copy import deepcopy

from ..version import VERSION
from ..enum import ApplicationStatus, ChatType
from . import BaseModel


class Status(BaseModel):
    class Plugin(BaseModel):
        order: bool = True
        event: bool = True

    class Bot(BaseModel):
        id: int = -1
        nickname: str = ""

    version: str = VERSION
    app: ApplicationStatus = ApplicationStatus.STARTED
    plugin: Plugin = Plugin()
    bot: Bot = Bot()
    friends: list[int] = []
    groups: list[int] = []


class Settings(BaseModel):
    class Security(BaseModel):
        class Webhook(BaseModel):
            token: str = secrets.token_urlsafe(32)

        class JWT(BaseModel):
            secret: str = secrets.token_urlsafe(32)
            algorithm: str = "HS256"

        class Admin(BaseModel):
            password: str = ""

        webhook: Webhook = Webhook()
        jwt: JWT = JWT()
        admin: Admin = Admin()

    class Mirai(BaseModel):
        class API(BaseModel):
            base_url: str = "http://127.0.0.1:9000"

        api: API = API()

    security: Security = Security()
    mirai: Mirai = Mirai()

    def update(self, settings: dict) -> None:
        self.security.webhook.token = settings["security"]["webhook"]["token"]
        self.security.jwt.secret = settings["security"]["jwt"]["secret"]
        self.security.jwt.algorithm = settings["security"]["jwt"]["algorithm"]
        self.security.admin.password = settings["security"]["admin"]["password"]
        self.mirai.api.base_url = settings["mirai"]["api"]["base_url"]


class PluginSettings:
    _plugin_settings: dict[str, dict] = {}

    @classmethod
    def set(cls, *, plugin: str, settings: dict) -> None:
        cls._plugin_settings[plugin] = deepcopy(settings)

    @classmethod
    def get(cls, *, plugin: str) -> dict:
        return deepcopy(cls._plugin_settings.setdefault(plugin, {}))  # Return copy to prevent modification

    @classmethod
    def dict(cls) -> dict:
        return deepcopy(cls._plugin_settings)


class ChatSettings:
    _chat_settings: dict[ChatType, dict[int, dict[str, dict]]] = {
        ChatType.FRIEND: {},
        ChatType.GROUP: {},
        ChatType.TEMP: {}
    }

    @classmethod
    def set(cls, *, chat_type: ChatType, chat_id: int, setting_group: str, settings: dict) -> None:
        cls._chat_settings[chat_type].setdefault(chat_id, {})[setting_group] = deepcopy(settings)

    @classmethod
    def get(cls, *, chat_type: ChatType, chat_id: int, setting_group: str) -> dict:
        return cls._chat_settings[chat_type].setdefault(chat_id, {}).setdefault(setting_group, {})

    @classmethod
    def dict(cls) -> dict:
        return deepcopy(cls._chat_settings)


class Replies:
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
    def set(cls, *, reply_group: str, replies: dict) -> None:
        cls._replies[reply_group] = deepcopy(replies)

    @classmethod
    def get(cls, *, reply_group: str) -> dict:
        return deepcopy(cls._replies.setdefault(reply_group, {}))  # Return copy to prevent modification

    @classmethod
    def get_reply(cls, *, reply_group: str, reply_key: str) -> str:
        return cls._replies[reply_group][reply_key]

    @classmethod
    def dict(cls) -> dict:
        return deepcopy(cls._replies)
