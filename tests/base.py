from unittest.mock import patch, MagicMock

patch("app.log")
patch("app.database")
patch("app.config")
patch("app.internal.network")

from app.config import status, chat_settings
from app.version import VERSION
from app.internal.enum import AppStatus, ChatType

status.update({
    "app": AppStatus.RUNNING,
    "version": VERSION,
    "report": {
        "order": True,
        "event": True
    },
    "bot": {
        "id": 99999,
        "nickname": "Shinji"
    }
})


def plugin_init(self, order: str, order_content: str) -> None:
    # Constructor of DiceRobotPlugin
    self.settings = self.default_settings
    self.replies = self.default_replies

    # Constructor of OrderPlugin
    self.chat_type = ChatType.GROUP
    self.chat_id = 12345
    self.chat_settings = self.default_chat_settings

    self.message_chain = MagicMock()
    self.order = order
    self.order_content = order_content

    self.reply_variables = {
        "机器人QQ": 10000,
        "机器人QQ号": 10000,
        "机器人": "Lilith",
        "机器人昵称": "Lilith",
        "群号": 12345,
        "群名": "Nerv",
        "发送者QQ": 88888,
        "发送者QQ号": 88888,
        "发送者": "Kaworu",
        "发送者昵称": "Kaworu"
    }

    chat_settings[self.chat_type].setdefault(self.chat_id, {}).setdefault("dicerobot", {})


def reply_to_sender(self, reply_messages: str) -> None:
    print(self.format_reply(reply_messages))


from plugins import OrderPlugin

OrderPlugin.__init__ = plugin_init
OrderPlugin.reply_to_sender = reply_to_sender
