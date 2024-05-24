from abc import ABC, abstractmethod
from typing import Type, Any
import copy

from app.config import Config, status, plugin_settings, replies, chat_settings
from app.exceptions import OrderException
from app.internal.message import Message, Plain, MessageChain, FriendMessage, GroupMessage, TempMessage
from app.internal.event import Event
from app.internal.enum import ChatType
from app.internal.network import (
    send_friend_message as mirai_send_friend_message, send_group_message as mirai_send_group_message,
    send_temp_message as mirai_send_temp_message
)


class DiceRobotPlugin(ABC):
    name: str
    display_name: str
    description: str
    version: str

    default_settings: dict[str] = {}
    default_replies: dict[str] = {}
    supported_reply_variables: list[str] = []

    @classmethod
    def init_plugin(cls) -> None:
        pass

    @classmethod
    def get_setting(cls, key: str, group: str = None) -> Any:
        if group:
            return plugin_settings[group][key]
        else:
            return plugin_settings[cls.name][key]

    @classmethod
    def get_reply(cls, key: str, group: str = None) -> str:
        if group:
            return replies[group][key]
        else:
            return replies[cls.name][key]

    @abstractmethod
    def __call__(self) -> None:
        pass


class OrderPlugin(DiceRobotPlugin):
    supported_reply_variables: list[str] = [
        "机器人昵称",
        "机器人QQ",
        "机器人QQ号",
        "群名",
        "群号",
        "昵称",
        "发送者昵称",
        "发送者QQ",
        "发送者QQ号"
    ]
    default_chat_settings = {}

    orders: str | list[str]
    priority: int = 100

    def __init__(self, message_chain: MessageChain, order: str, order_content: str) -> None:
        super().__init__()

        self.chat_type = ChatType.OTHER
        self.chat_id = -1
        self.chat_settings = Config()

        self.message_chain = message_chain
        self.order = order
        self.order_content = order_content

        self.reply_variables = {}

        self.load_chat_config()
        self.init_reply_variables()

    @abstractmethod
    def __call__(self) -> None:
        pass

    def load_chat_config(self) -> None:
        if type(self.message_chain) is FriendMessage:
            self.chat_type = ChatType.FRIEND
            self.chat_id = self.message_chain.sender.id
        elif type(self.message_chain) is GroupMessage:
            self.chat_type = ChatType.GROUP
            self.chat_id = self.message_chain.sender.group.id
        elif type(self.message_chain) is TempMessage:
            self.chat_type = ChatType.TEMP
            self.chat_id = self.message_chain.sender.group.id
            self.chat_settings = copy.deepcopy(self.default_chat_settings)

            return

        # Create chat settings if not exists
        if self.chat_id not in chat_settings[self.chat_type]:
            chat_settings[self.chat_type][self.chat_id] = {}

        if "dicerobot" not in chat_settings[self.chat_type][self.chat_id]:
            chat_settings[self.chat_type][self.chat_id]["dicerobot"] = {}

        if self.name not in chat_settings[self.chat_type][self.chat_id]:
            chat_settings[self.chat_type][self.chat_id][self.name] = copy.deepcopy(self.default_chat_settings)

        self.chat_settings = chat_settings[self.chat_type][self.chat_id][self.name]

    def get_chat_setting(self, key: str, group: str = None) -> Any:
        if group:
            return chat_settings[self.chat_type][self.chat_id][group][key]
        else:
            return self.chat_settings[key]

    def set_chat_setting(self, key: str, value: Any, group: str = None) -> None:
        if group:
            chat_settings[self.chat_type][self.chat_id][group][key] = value
        else:
            self.chat_settings[key] = value

    def init_reply_variables(self) -> None:
        bot_id = status["bot"]["id"]
        bot_nickname = chat_settings[self.chat_type][self.chat_id]["dicerobot"].setdefault("nickname", "")
        sender_id = self.message_chain.sender.id,
        sender_nickname = self.message_chain.sender.member_name if isinstance(self.message_chain, GroupMessage) else self.message_chain.sender.nickname

        self.reply_variables = {
            "机器人QQ": bot_id,
            "机器人QQ号": bot_id,
            "机器人": bot_nickname if bot_nickname else status["bot"]["nickname"],
            "机器人昵称": bot_nickname if bot_nickname else status["bot"]["nickname"],
            "群号": self.message_chain.sender.group.id if isinstance(self.message_chain, GroupMessage) else "",
            "群名": self.message_chain.sender.group.name if isinstance(self.message_chain, GroupMessage) else "",
            "发送者QQ": sender_id,
            "发送者QQ号": sender_id,
            "发送者": sender_nickname,
            "发送者昵称": sender_nickname
        }

    def check_enabled(self) -> bool:
        return chat_settings[self.chat_type][self.chat_id]["dicerobot"].setdefault("enabled", True)

    def check_order_content(self) -> None:
        if not self.order_content:
            raise OrderException(self.get_reply(group="dicerobot", key="order_invalid"))

    def update_reply_variables(self, d: dict[str, Any]) -> None:
        self.reply_variables |= d

    def format_reply(self, reply: str) -> str:
        for key, value in self.reply_variables.items():
            reply = reply.replace(f"{{&{key}}}", str(value))

        return reply

    def reply_to_sender(self, reply: str | list[Message]) -> None:
        if isinstance(reply, str):
            reply = [Plain.model_validate({"type": "Plain", "text": self.format_reply(reply)})]

        self.reply_to_message_sender(self.message_chain, reply)

    @classmethod
    def reply_to_message_sender(cls, message_chain: MessageChain, reply: str | list[Message]) -> None:
        if type(message_chain) is FriendMessage:
            cls.send_friend_message(message_chain.sender.id, reply)
        elif type(message_chain) is GroupMessage:
            cls.send_group_message(message_chain.sender.group.id, reply)
        elif type(message_chain) is TempMessage:
            cls.send_temp_message(message_chain.sender.id, message_chain.sender.group.id, reply)
        else:
            raise RuntimeError("Invalid message chain type")

    @staticmethod
    def send_friend_message(chat_id: int, message: str | list[Message]) -> None:
        if isinstance(message, str):
            message = [Plain.model_validate({"type": "Plain", "text": message})]

        mirai_send_friend_message({
            "target": chat_id,
            "message_chain": message
        })

    @staticmethod
    def send_group_message(chat_id: int, message: str | list[Message]) -> None:
        if isinstance(message, str):
            message = [Plain.model_validate({"type": "Plain", "text": message})]

        mirai_send_group_message({
            "target": chat_id,
            "message_chain": message
        })

    @staticmethod
    def send_temp_message(target_id: int, group_id: int, message: str | list[Message]) -> None:
        if isinstance(message, str):
            message = [Plain.model_validate({"type": "Plain", "text": message})]

        mirai_send_temp_message({
            "qq": target_id,
            "group": group_id,
            "message_chain": message
        })

    @classmethod
    def send_friend_or_temp_message(cls, target_id: int, group_id: int, messages: str | list[Message]) -> None:
        if target_id in status["friends"]:
            cls.send_friend_message(target_id, messages)
        else:
            cls.send_temp_message(target_id, group_id, messages)


class EventPlugin(DiceRobotPlugin):
    events = Type[Event] | list[Type[Event]]

    def __init__(self, event: Event) -> None:
        super().__init__()

        self.event = event
        self.reply_variables = {}

    @abstractmethod
    def __call__(self) -> None:
        pass
