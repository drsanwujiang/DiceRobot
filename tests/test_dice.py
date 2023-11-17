import sys
from unittest.mock import MagicMock

import pytest

sys.modules["app.log"] = MagicMock()
sys.modules["app.database"] = MagicMock()
sys.modules["app.config"] = MagicMock()
sys.modules["app.internal.network"] = MagicMock()

from app.internal.enum import ChatType
from app.exceptions import OrderException
from plugins.dicerobot.order.dice import Dice as _Dice


class Dice(_Dice):
    # noinspection PyMissingConstructor
    def __init__(self, order: str, order_content: str) -> None:
        # Constructor of DiceRobotPlugin
        self.settings = Dice.default_settings
        self.replies = Dice.default_replies

        # Constructor of OrderPlugin
        self.chat_type = ChatType.FRIEND
        self.chat_id = 10000
        self.chat_settings = Dice.default_chat_settings

        self.message_chain = MagicMock()
        self.order = order
        self.order_content = order_content

        self.reply_variables = {
            "发送者": "<发送者>"
        }

        # Constructor of Dice
        self.expression = "d"
        self.reason = ""
        self.detailed_dice_result = ""
        self.dice_result = ""
        self.final_result = ""

    def reply_to_sender(self, reply_messages: str) -> None:
        print(self.format_reply(reply_messages))


def test_dice():
    dice = Dice(order="r", order_content="")
    dice()
    assert dice.expression == "D100"
    assert dice.reason == ""

    dice = Dice(order="r", order_content="d")
    dice()
    assert dice.expression == "D100"
    assert dice.reason == ""

    dice = Dice(order="r", order_content="d100")
    dice()
    assert dice.expression == "D100"
    assert dice.reason == ""

    dice = Dice(order="r", order_content="10d100k2")
    dice()
    assert dice.expression == "10D100K2"
    assert dice.reason == ""

    dice = Dice(order="r", order_content="(5d100+d30+666)*5-2+6d50k2x2+6X5 掷骰原因")
    dice()
    assert dice.expression == "(5D100+D30+666)×5-2+6D50K2×2+6×5"
    assert dice.reason == "掷骰原因"

    dice = Dice(order="r", order_content="d50Reason")
    dice()
    assert dice.expression == "D50"
    assert dice.reason == "Reason"

    dice = Dice(order="r", order_content="d50 Reason")
    dice()
    assert dice.expression == "D50"
    assert dice.reason == "Reason"

    dice = Dice(order="r", order_content="dReason")
    dice()
    assert dice.expression == "D100"
    assert dice.reason == "Reason"

    dice = Dice(order="r", order_content="d 50")
    dice()
    assert dice.expression == "D100"
    assert dice.reason == "50"

    with pytest.raises(OrderException):
        dice = Dice(order="r", order_content="10d100kk2+5")
        dice.parse_content()
        dice.calculate_expression()

    with pytest.raises(OrderException):
        dice = Dice(order="r", order_content="(10d100k2+5")
        dice.parse_content()
        dice.calculate_expression()
