from abc import ABC, abstractmethod
from typing import Type, Any
import copy

from app.config import Config, status, plugin_settings, replies, chat_settings
from app.internal.message import Message, MessageChain, FriendMessage, GroupMessage, TempMessage
from app.internal.event import Event
from app.internal.enum import ChatType
from app.internal.util import reply_to_sender


class DiceRobotPlugin(ABC):
    name: str
    display_name: str
    description: str
    version: str

    default_settings: dict[str] = {}
    default_replies: dict[str] = {}
    supported_reply_variables: list[str] = []

    def __init__(self):
        self.settings: Config[str] = plugin_settings[self.__class__.name]
        self.replies: Config[str, str] = replies[self.__class__.name]

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
            chat_settings[self.chat_type].setdefault(self.chat_id, {}).setdefault("dicerobot", {})

            if self.__class__.default_chat_settings:
                self.chat_settings = chat_settings[self.chat_type][self.chat_id] \
                    .setdefault(self.__class__.name, self.__class__.default_chat_settings)
        elif type(self.message_chain) is GroupMessage:
            self.chat_type = ChatType.GROUP
            self.chat_id = self.message_chain.sender.group.id
            chat_settings[self.chat_type].setdefault(self.chat_id, {}).setdefault("dicerobot", {})

            if self.__class__.default_chat_settings:
                self.chat_settings = chat_settings[self.chat_type][self.chat_id] \
                    .setdefault(self.__class__.name, self.__class__.default_chat_settings)
        elif type(self.message_chain) is TempMessage:
            self.chat_type = ChatType.TEMP
            self.chat_id = self.message_chain.sender.group.id

            if self.__class__.default_chat_settings:
                self.chat_settings = copy.deepcopy(self.__class__.default_chat_settings)

    def check_enabled(self) -> bool:
        return chat_settings[self.chat_type][self.chat_id]["dicerobot"].setdefault("enabled", True)

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

    def update_reply_variables(self, d: dict[str, Any]) -> None:
        self.reply_variables |= d

    def format_reply(self, reply: str) -> str:
        for key, value in self.reply_variables.items():
            reply = reply.replace(f"{{&{key}}}", str(value))

        return reply

    def reply_to_sender(self, reply_messages: str | list[Message]) -> None:
        if isinstance(reply_messages, str):
            reply_messages = self.format_reply(reply_messages)

        reply_to_sender(self.message_chain, reply_messages)


class EventPlugin(DiceRobotPlugin):
    events = Type[Event] | list[Type[Event]]

    def __init__(self, event: Event) -> None:
        super().__init__()

        self.event = event
        self.reply_variables = {}

    @abstractmethod
    def __call__(self) -> None:
        pass
