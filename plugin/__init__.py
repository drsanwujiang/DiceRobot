from abc import ABC, abstractmethod
from typing import Type, Any
from copy import deepcopy

from app.config import status, plugin_settings, chat_settings, replies
from app.exceptions import OrderInvalidError, OrderRepetitionExceededError
from app.enum import ChatType, PrivateMessageSubType, GroupMessageSubType
from app.utils import deep_update
from app.models.report.message import Message, PrivateMessage, GroupMessage
from app.models.report.notice import Notice
from app.models.report.request import Request
from app.models.report.segment import Segment, Text
from app.network.napcat import (
    send_private_message as napcat_send_private_message, send_group_message as napcat_send_group_message
)


class DiceRobotPlugin(ABC):
    """DiceRobot plugin.

    Attributes:
        name: Plugin name.
        display_name: Plugin display name.
        description: Plugin description.
        version: Plugin version.
        default_plugin_settings: Default plugin settings.
        default_replies: Default plugin replies.
        supported_reply_variables: Supported reply variable list.
    """

    name: str
    display_name: str
    description: str
    version: str

    default_plugin_settings: dict[str] = {}

    default_replies: dict[str] = {}
    supported_reply_variables: list[str] = []

    @classmethod
    def load(cls) -> None:
        """Load plugin settings and replies."""

        plugin_settings.set(plugin=cls.name, settings=deep_update(
            {"enabled": True},
            deepcopy(cls.default_plugin_settings), plugin_settings.get(plugin=cls.name)
        ))
        replies.set_replies(group=cls.name, replies=deep_update(
            deepcopy(cls.default_replies),
            replies.get_replies(group=cls.name)
        ))

    @classmethod
    async def initialize(cls) -> None:
        """Initialize plugin.

        This method is called when the plugin is loaded. Usually used to initialize some resources or tasks that the
        plugin will use.
        """

        pass

    @classmethod
    def get_plugin_setting(cls, *, plugin: str = None, key: str) -> Any:
        """Get plugin setting.

        This method should only be used to dynamically get plugin settings within a class method. For normal execution,
        use `self.plugin_settings` instead.

        Args:
            plugin: Plugin name.
            key: Setting key.

        Returns:
            Setting.
        """

        return plugin_settings.get(plugin=plugin or cls.name)[key]

    @classmethod
    def get_reply(cls, *, group: str = None, key: str) -> str:
        """Get plugin reply.

        This method should only be used to dynamically get plugin reply within a class method. For normal execution,
        use ``self.replies`` instead.

        Args:
            group: Reply group.
            key: Reply key.

        Returns:
            Reply.
        """

        return replies.get_reply(group=group or cls.name, key=key)

    def __init__(self) -> None:
        """Initialize DiceRobot plugin."""

        self.plugin_settings = plugin_settings.get(plugin=self.name)
        self.replies = replies.get_replies(group=self.name)

    @abstractmethod
    async def __call__(self) -> None:
        """Execute the plugin.

        When there is a message that meets the conditions, an instance of the plugin will be created, then this method
        will be executed. The plugin should implement its main logic here.
        """

        pass

    def save_plugin_settings(self) -> None:
        """Save plugin settings.

        Plugin settings must be saved explicitly to avoid inappropriate modification.
        """

        plugin_settings.set(plugin=self.name, settings=self.plugin_settings)


class OrderPlugin(DiceRobotPlugin):
    """DiceRobot order plugin.

    Attributes:
        default_chat_settings: Default chat settings.

        orders: Order or orders that trigger the plugin.
        priority: The priority of the plugin. A larger value means a higher priority.
    """

    default_chat_settings = {}

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

    orders: str | list[str]
    priority: int = 100
    max_repetition: int = 1

    def __init__(self, message: Message, order: str, order_content: str, repetition: int = 1) -> None:
        """Initialize order plugin.

        Args:
            message: Message that triggered the plugin.
            order: Order that triggered the plugin.
            order_content: Order content.
            repetition: Repetitions.
        """

        super().__init__()

        self.chat_type: ChatType
        self.chat_id = -1

        self.message = message
        self.order = order
        self.order_content = order_content
        self.repetition = repetition

        self.reply_variables = {}

        self._load_chat()
        self._init_reply_variables()

    @abstractmethod
    async def __call__(self) -> None:
        pass

    def _load_chat(self) -> None:
        """Load chat information and settings."""

        if isinstance(self.message, PrivateMessage) and self.message.sub_type == PrivateMessageSubType.FRIEND:
            self.chat_type = ChatType.FRIEND
            self.chat_id = self.message.user_id
        elif isinstance(self.message, GroupMessage) and self.message.sub_type == GroupMessageSubType.NORMAL:
            self.chat_type = ChatType.GROUP
            self.chat_id = self.message.group_id
        else:
            raise ValueError

        # Settings used by the plugin in this chat
        self.chat_settings = chat_settings.get(chat_type=self.chat_type, chat_id=self.chat_id, setting_group=self.name)
        # Settings used by DiceRobot in this chat (bot nickname etc.)
        self.dicerobot_chat_settings = chat_settings.get(chat_type=self.chat_type, chat_id=self.chat_id, setting_group="dicerobot")

        if not self.chat_settings:
            # Use default chat settings in a new chat
            self.chat_settings.update(deepcopy(self.default_chat_settings))

    def _init_reply_variables(self) -> None:
        """Initialize common used reply variables."""

        bot_id = status.bot.id
        bot_nickname = self.dicerobot_chat_settings.setdefault("nickname", "")

        self.reply_variables = {
            "机器人QQ": bot_id,
            "机器人QQ号": bot_id,
            "机器人": bot_nickname if bot_nickname else status.bot.nickname,
            "机器人昵称": bot_nickname if bot_nickname else status.bot.nickname,
            "发送者QQ": self.message.user_id,
            "发送者QQ号": self.message.user_id,
            "发送者": self.message.sender.nickname,
            "发送者昵称": self.message.sender.nickname
        }

    def check_enabled(self) -> bool:
        """Check whether the plugin is enabled in this chat.

        By default, it will check whether DiceRobot is enabled in this chat. Plugin can override this method as needed.

        Returns:
            Whether the plugin is enabled.
        """

        return self.dicerobot_chat_settings.setdefault("enabled", True)

    def check_order_content(self) -> None:
        """Check whether the order content is valid.

        This method should not return anything. If the order content is invalid, it should raise an `OrderInvalidError`
        exception.

        By default, it will check whether the order content is empty. Plugin can override this method as needed.

        Raises:
            OrderInvalidError: Order content is invalid.
        """

        if not self.order_content:
            raise OrderInvalidError

    def check_repetition(self) -> None:
        """Check whether the repetition is valid.

        This method should not return anything. If the repetition is invalid, it should raise an
        `OrderRepetitionExceededError` exception.

        By default, it will check whether the repetition exceeds the maximum. The plugin can modify the `max_repetition`
        attribute to control the maximum of repetitions, or override this method as needed.

        Raises:
            OrderRepetitionExceededError: Repetition exceeds the maximum.
        """

        if self.repetition > self.max_repetition:
            raise OrderRepetitionExceededError

    def update_reply_variables(self, d: dict[str, Any]) -> None:
        """Update reply variables used in replies.

        Args:
            d: Dictionary of reply variables.
        """

        self.reply_variables |= d

    def format_reply(self, reply: str) -> str:
        """Replace the placeholders (reply variables) in the reply with actual values.

        Args:
            reply: Reply.
        """

        for key, value in self.reply_variables.items():
            reply = reply.replace(f"{{&{key}}}", str(value))

        return reply

    async def reply_to_sender(self, reply: str | list[Segment]) -> None:
        """Send reply to the sender.

        Args:
            reply: Reply string or message. Reply string will be formatted and converted to a text message.
        """

        if isinstance(reply, str):
            reply = [Text(data=Text.Data(text=self.format_reply(reply)))]

        await self.reply_to_message_sender(self.message, reply)

    @classmethod
    async def reply_to_message_sender(cls, message: Message, reply: str | list[Segment]) -> None:
        """Send reply to the sender of the specific message.

        Args:
            message: Message.
            reply: Reply string or message.
        """

        if isinstance(message, PrivateMessage) and message.sub_type == PrivateMessageSubType.FRIEND:
            await cls.send_friend_message(message.user_id, reply)
        elif isinstance(message, GroupMessage) and message.sub_type == GroupMessageSubType.NORMAL:
            await cls.send_group_message(message.group_id, reply)
        else:
            raise RuntimeError("Invalid message type or sub type")

    @staticmethod
    async def send_friend_message(user_id: int, message: str | list[Segment]) -> None:
        """Send the message to the friend.

        Args:
            user_id: Friend ID.
            message: String or segments. String will be converted to a text message.
        """

        if isinstance(message, str):
            message = [Text(data=Text.Data(text=message))]

        await napcat_send_private_message(user_id, message)

    @staticmethod
    async def send_group_message(group_id: int, message: str | list[Message]) -> None:
        """Send the message to the group.

        Args:
            group_id: Group ID.
            message: String or segments. String will be converted to a text message.
        """

        if isinstance(message, str):
            message = [Text(data=Text.Data(text=message))]

        await napcat_send_group_message(group_id, message)


class EventPlugin(DiceRobotPlugin):
    """DiceRobot event plugin.

    Attributes:
        events: (class attribute) Events that can trigger the plugin.
    """

    events: list[Type[Notice | Request]] = []

    def __init__(self, event: Notice | Request) -> None:
        """Initialize event plugin.

        Args:
            event: Event that triggered the plugin.
        """

        super().__init__()

        self.event = event
        self.reply_variables = {}

    @abstractmethod
    async def __call__(self) -> None:
        pass
