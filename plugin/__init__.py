from typing import TYPE_CHECKING, Type, Any
from abc import ABC, abstractmethod
from copy import deepcopy
import re

from app.exceptions import OrderInvalidError, OrderRepetitionExceededError
from app.enum import ChatType
from app.utils import deep_update
from app.models.report.message import Message
from app.models.report.notice import Notice
from app.models.report.request import Request
from app.models.report.segment import Segment, Text

if TYPE_CHECKING:
    from app.context import AppContext

__all__ = [
    "DiceRobotPlugin",
    "OrderPlugin",
    "EventPlugin"
]


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
    default_plugin_settings: dict = {}
    default_replies: dict = {}
    supported_reply_variables: list[str] = []

    @classmethod
    def load(cls, context: "AppContext") -> None:
        """Load plugin settings and replies."""

        loaded_settings = context.plugin_settings.get(plugin=cls.name)
        loaded_settings.setdefault("enabled", True)  # Ensure the plugin is enabled by default

        for key in loaded_settings.copy().keys():
            if key == "enabled":
                continue
            elif key not in cls.default_plugin_settings:
                # Remove settings that are not in the default settings
                del loaded_settings[key]

        context.plugin_settings.set(plugin=cls.name, settings=deep_update(
            deepcopy(cls.default_plugin_settings), loaded_settings
        ))

        loaded_replies = context.replies.get_replies(group=cls.name)

        for key in loaded_replies.copy().keys():
            if key not in cls.default_replies:
                # Remove replies that are not in the default replies
                del loaded_replies[key]

        context.replies.set_replies(group=cls.name, replies=deep_update(
            deepcopy(cls.default_replies), loaded_replies
        ))

    @classmethod
    async def initialize(cls, context: "AppContext") -> None:
        """Initialize plugin.

        This method is called when the plugin is loaded. Usually used to initialize some resources or tasks that the
        plugin will use.
        """

        ...

    def __init__(self, context: "AppContext") -> None:
        """Initialize DiceRobot plugin.

        Args:
            context: Application context.
        """

        self.context = context
        self.plugin_settings = context.plugin_settings.get(plugin=self.name)
        self.replies = context.replies.get_replies(group=self.name)

    @abstractmethod
    async def __call__(self) -> None:
        """Execute the plugin.

        When there is a message that meets the conditions, an instance of the plugin will be created, then this method
        will be executed. The plugin should implement its main logic here.
        """

        ...

    def save_plugin_settings(self) -> None:
        """Save plugin settings.

        Plugin settings must be saved explicitly to avoid inappropriate modification.
        """

        self.context.plugin_settings.set(plugin=self.name, settings=self.plugin_settings)


class OrderPlugin(DiceRobotPlugin):
    """DiceRobot order plugin.

    Attributes:
        priority: The priority of the plugin. A larger value means a higher priority.
        max_repetition: the maximum of repetitions of the plugin.
        orders: Order or orders that trigger the plugin.
        default_chat_settings: Default chat settings.
    """

    priority: int = 100
    max_repetition: int = 1
    orders: str | list[str]
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

    def __init__(self, context: "AppContext", message: Message, order: str, order_content: str, repetition: int = 1) -> None:
        """Initialize order plugin.

        Args:
            context: Application context.
            message: Message that triggered the plugin.
            order: Order that triggered the plugin.
            order_content: Order content.
            repetition: Repetitions.
        """

        super().__init__(context)

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
        ...

    def _load_chat(self) -> None:
        """Load chat information and settings."""

        if self.message.from_group:
            self.chat_type = ChatType.GROUP
            self.chat_id = self.message.group_id
        elif self.message.from_friend:
            self.chat_type = ChatType.FRIEND
            self.chat_id = self.message.user_id
        elif self.message.from_group_temp:
            self.chat_type = ChatType.TEMP
            self.chat_id = self.message.user_id
        else:
            raise ValueError

        # Settings used by the plugin in this chat
        self.chat_settings = \
            self.context.chat_settings.get(chat_type=self.chat_type, chat_id=self.chat_id, settings_group=self.name)
        # Settings used by DiceRobot in this chat (bot nickname etc.)
        self.dicerobot_chat_settings = \
            self.context.chat_settings.get(chat_type=self.chat_type, chat_id=self.chat_id, settings_group="dicerobot")

        if not self.chat_settings:
            # Use default chat settings in a new chat
            self.chat_settings.update(deepcopy(self.default_chat_settings))

    def _init_reply_variables(self) -> None:
        """Initialize common used reply variables."""

        bot_id = self.context.status.bot.id
        bot_nickname = self.dicerobot_chat_settings.setdefault("nickname", "")

        self.reply_variables = {
            "机器人QQ": bot_id,
            "机器人QQ号": bot_id,
            "机器人": bot_nickname if bot_nickname else self.context.status.bot.nickname,
            "机器人昵称": bot_nickname if bot_nickname else self.context.status.bot.nickname,
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

    def check_repetition(self, max_repetition: int = None) -> None:
        """Check whether the repetition is valid.

        This method should not return anything. If `max_repetition` is not provided, it will use the `max_repetition`
        attribute of the plugin. If the repetition exceeds the maximum, an `OrderRepetitionExceededError` exception
        will be raised.

        Raises:
            OrderRepetitionExceededError: Repetition exceeds the maximum.
        """

        max_repetition = max_repetition if max_repetition is not None else self.max_repetition

        if self.repetition > max_repetition:
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

        def replacer(match: re.Match) -> str:
            return str(self.reply_variables.get(match.group(1), match.group(0)))

        return re.sub(r"\{&(.+?)}", replacer, reply)

    async def send_group_message(self, group_id: int, message: str | list[Message]) -> None:
        """Send the message to the group.

        Args:
            group_id: Group ID.
            message: String or segments. String will be converted to a text message.
        """

        if isinstance(message, str):
            message = [Text(data=Text.Data(text=message))]

        await self.context.network_manager.napcat.send_group_message(group_id, message)

    async def send_private_message(self, user_id: int, message: str | list[Segment]) -> None:
        """Send the message to the user.

        Args:
            user_id: User ID.
            message: String or segments. String will be converted to a text message.
        """

        if isinstance(message, str):
            message = [Text(data=Text.Data(text=message))]

        await self.context.network_manager.napcat.send_private_message(user_id, message)

    async def reply_to_message_sender(self, message: Message, reply: str | list[Segment]) -> None:
        """Send reply to the sender of the specific message.

        Args:
            message: Message.
            reply: Reply string or message.
        """

        if message.from_group:
            await self.send_group_message(message.group_id, reply)
        elif message.from_friend or message.from_group_temp:
            await self.send_private_message(message.user_id, reply)
        else:
            raise RuntimeError("Invalid message type or sub type")

    async def reply_to_sender(self, reply: str | list[Segment]) -> None:
        """Send reply to the sender.

        Args:
            reply: Reply string or message. Reply string will be formatted and converted to a text message.
        """

        if isinstance(reply, str):
            reply = [Text(data=Text.Data(text=self.format_reply(reply)))]

        await self.reply_to_message_sender(self.message, reply)


class EventPlugin(DiceRobotPlugin):
    """DiceRobot event plugin.

    Attributes:
        events: (class attribute) Events that can trigger the plugin.
    """

    events: list[Type[Notice | Request]] = []

    def __init__(self, context: "AppContext", event: Notice | Request) -> None:
        """Initialize event plugin.

        Args:
            context: Application context.
            event: Event that triggered the plugin.
        """

        super().__init__(context)

        self.event = event
        self.reply_variables = {}

    @abstractmethod
    async def __call__(self) -> None:
        ...
