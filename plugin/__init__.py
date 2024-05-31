from abc import ABC, abstractmethod
from typing import Type, Any
from copy import deepcopy

from app.config import status, plugin_settings, chat_settings, replies
from app.exceptions import OrderInvalidError
from app.enum import ChatType
from app.utils import deep_update
from app.models.message import Message, Plain, MessageChain, FriendMessage, GroupMessage, TempMessage
from app.models.event import Event
from app.models.network.mirai import SendFriendMessageRequest, SendGroupMessageRequest, SendTempMessageRequest
from app.network.mirai import (
    send_friend_message as mirai_send_friend_message, send_group_message as mirai_send_group_message,
    send_temp_message as mirai_send_temp_message
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
            {"enabled": True}, deepcopy(cls.default_plugin_settings), plugin_settings.get(plugin=cls.name)
        ))
        replies.set(reply_group=cls.name, replies=deep_update(
            deepcopy(cls.default_replies), replies.get(reply_group=cls.name)
        ))

    @classmethod
    def initialize(cls) -> None:
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
    def get_reply(cls, *, reply_group: str = None, reply_key: str) -> str:
        """Get plugin reply.

        This method should only be used to dynamically get plugin reply within a class method. For normal execution,
        use ``self.replies`` instead.

        Args:
            reply_group: Reply group.
            reply_key: Reply key.

        Returns:
            Reply.
        """

        return replies.get_reply(reply_group=reply_group or cls.name, reply_key=reply_key)

    def __init__(self) -> None:
        """Initialize DiceRobot plugin."""

        self.plugin_settings = plugin_settings.get(plugin=self.name)
        self.replies = replies.get(reply_group=self.name)

    @abstractmethod
    def __call__(self) -> None:
        """Execute the plugin.

        When there is a message that meets the conditions, an instance of the plugin will be created, then this method
        will be executed. The plugin should implement its main logic here.
        """

        pass


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

    def __init__(self, message_chain: MessageChain, order: str, order_content: str) -> None:
        """Initialize order plugin.

        Args:
            message_chain: Message chain that triggered the plugin.
            order: Order that triggered the plugin.
            order_content: Order content.
        """

        super().__init__()

        self.chat_type: ChatType
        self.chat_id = -1

        self.message_chain = message_chain
        self.order = order
        self.order_content = order_content

        self.reply_variables = {}

        self._load_chat()
        self._init_reply_variables()

    @abstractmethod
    def __call__(self) -> None:
        pass

    def _load_chat(self) -> None:
        """Load chat information and settings."""

        if type(self.message_chain) is FriendMessage:
            self.chat_type = ChatType.FRIEND
            self.chat_id = self.message_chain.sender.id
        elif type(self.message_chain) is GroupMessage:
            self.chat_type = ChatType.GROUP
            self.chat_id = self.message_chain.sender.group.id
        elif type(self.message_chain) is TempMessage:
            self.chat_type = ChatType.TEMP
            self.chat_id = self.message_chain.sender.group.id

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
        sender_id = self.message_chain.sender.id,
        sender_nickname = self.message_chain.sender.member_name if isinstance(self.message_chain, GroupMessage) else self.message_chain.sender.nickname

        self.reply_variables = {
            "机器人QQ": bot_id,
            "机器人QQ号": bot_id,
            "机器人": bot_nickname if bot_nickname else status.bot.nickname,
            "机器人昵称": bot_nickname if bot_nickname else status.bot.nickname,
            "群号": self.message_chain.sender.group.id if isinstance(self.message_chain, GroupMessage) else "",
            "群名": self.message_chain.sender.group.name if isinstance(self.message_chain, GroupMessage) else "",
            "发送者QQ": sender_id,
            "发送者QQ号": sender_id,
            "发送者": sender_nickname,
            "发送者昵称": sender_nickname
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

    def reply_to_sender(self, reply: str | list[Message]) -> None:
        """Send reply to the sender of the message chain that triggered the plugin.

        Args:
            reply: Reply string or messages. Reply string will be formatted and converted to a plain message.
        """

        if isinstance(reply, str):
            reply = [Plain.model_validate({"type": "Plain", "text": self.format_reply(reply)})]

        self.reply_to_message_sender(self.message_chain, reply)

    @classmethod
    def reply_to_message_sender(cls, message_chain: MessageChain, reply: str | list[Message]) -> None:
        """Send reply to the sender of the message chain.

        Args:
            message_chain: Message chain.
            reply: Reply string or messages.
        """

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
        """Send message to friend.

        Args:
            chat_id: Friend ID.
            message: Message string or message list. Message string will be converted to a plain message.
        """

        if isinstance(message, str):
            message = [Plain.model_validate({"type": "Plain", "text": message})]

        mirai_send_friend_message(SendFriendMessageRequest(
            target=chat_id,
            message_chain=message
        ))

    @staticmethod
    def send_group_message(chat_id: int, message: str | list[Message]) -> None:
        """Send message to group.

        Args:
            chat_id: Group ID.
            message: Message string or message list. Message string will be converted to a plain message.
        """

        if isinstance(message, str):
            message = [Plain.model_validate({"type": "Plain", "text": message})]

        mirai_send_group_message(SendGroupMessageRequest(
            target=chat_id,
            message_chain=message
        ))

    @staticmethod
    def send_temp_message(target_id: int, group_id: int, message: str | list[Message]) -> None:
        """Send message to temporary chat.

        Args:
            target_id: Target chat ID.
            group_id: Group ID.
            message: Message string or message list. Message string will be converted to a plain message.
        """

        if isinstance(message, str):
            message = [Plain.model_validate({"type": "Plain", "text": message})]

        mirai_send_temp_message(SendTempMessageRequest(
            qq=target_id,
            group=group_id,
            message_chain=message
        ))

    @classmethod
    def send_friend_or_temp_message(cls, target_id: int, group_id: int, messages: str | list[Message]) -> None:
        """Send message to friend or temporary chat.

        Args:
            target_id: Friend or temporary chat ID.
            group_id: Group ID if the target is a temporary chat.
            messages: Message string or message list. Message string will be converted to a plain message.
        """

        if target_id in status.bot.friends:
            cls.send_friend_message(target_id, messages)
        else:
            cls.send_temp_message(target_id, group_id, messages)


class EventPlugin(DiceRobotPlugin):
    """DiceRobot event plugin.

    Attributes:
        events: (class attribute) Event or events that trigger the plugin.
    """

    events = Type[Event] | list[Type[Event]]

    def __init__(self, event: Event) -> None:
        """Initialize event plugin.

        Args:
            event: Event that triggered the plugin.
        """

        super().__init__()

        self.event = event
        self.reply_variables = {}

    @abstractmethod
    def __call__(self) -> None:
        pass
