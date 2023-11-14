from abc import ABC, abstractmethod
from typing import Type

from app.config import Config, status, plugin_settings, replies, chat_settings
from app.internal.message import Message, MessageChain, FriendMessage, GroupMessage, TempMessage
from app.internal.event import Event
from app.internal.enum import ChatType
from app.internal.util import reply_to_sender


class DiceRobotPlugin(ABC):
    name: str
    description: str
    default_settings: dict[str] = {}
    default_replies: dict[str] = {}
    supported_reply_variables: list[str] = []

    def __init__(self):
        self.plugin_settings: Config[str] = plugin_settings[self.__class__.name]
        self.replies: Config[str, str] = replies[self.__class__.name]

    @abstractmethod
    def __call__(self) -> None:
        pass


class OrderPlugin(DiceRobotPlugin):
    supported_reply_variables: list[str] = ["机器人昵称", "机器人QQ", "机器人QQ号", "群名", "群号", "昵称", "发送者昵称", "发送者QQ", "发送者QQ号"]
    default_chat_settings = {}
    orders: str | list[str]
    default_priority: int = 100

    def __init__(self, message_chain: MessageChain, order: str, order_content: str) -> None:
        super().__init__()

        self.chat_type = ChatType.FRIEND if type(message_chain) == FriendMessage \
            else ChatType.GROUP if type(message_chain) == GroupMessage \
            else ChatType.TEMP if type(message_chain) == TempMessage \
            else ChatType.OTHER
        self.chat_id = message_chain.sender.id if isinstance(message_chain, FriendMessage) else message_chain.sender.group.id
        self.chat_settings: Config[str] = chat_settings[self.chat_type.value].setdefault(self.chat_id, {}).setdefault(self.__class__.name, self.__class__.default_chat_settings)

        self.message_chain = message_chain
        self.order = order
        self.order_content = order_content

        self.reply_variables = {
            "机器人": status["bot"]["nickname"],
            "机器人昵称": status["bot"]["nickname"],
            "机器人QQ": status["bot"]["id"],
            "机器人QQ号": status["bot"]["id"],
            "群名": message_chain.sender.group.name if isinstance(message_chain, GroupMessage) else "",
            "群号": message_chain.sender.group.id if isinstance(message_chain, GroupMessage) else "",
            "发送者": message_chain.sender.member_name if isinstance(message_chain, GroupMessage) else message_chain.sender.nickname,
            "发送者昵称": message_chain.sender.member_name if isinstance(message_chain, GroupMessage) else message_chain.sender.nickname,
            "发送者QQ": message_chain.sender.id,
            "发送者QQ号": message_chain.sender.id
        }

    @abstractmethod
    def __call__(self) -> None:
        pass

    def update_reply_variables(self, d: dict[str]) -> None:
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
