import pytest

from app.config import plugin_settings, replies
from app.exceptions import OrderException
from plugins.dicerobot.order.dice import Dice


plugin_settings[Dice.name] = Dice.default_settings
replies[Dice.name] = Dice.default_replies


def test_dice():
    Dice(order="r", order_content="")()
    Dice(order="r", order_content="d")()
    Dice(order="r", order_content="d100")()
    Dice(order="r", order_content="10d100k2")()
    Dice(order="r", order_content="(5d100+d30+666)*5-2+6d50k2*2+6 掷骰原因")()

    with pytest.raises(OrderException):
        Dice(order="r", order_content="10d100kk2+5")()
