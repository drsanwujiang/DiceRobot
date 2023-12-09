import pytest

import tests.base
from app.exceptions import OrderException
from plugins.dicerobot.order.bot import Bot


def test_bot():
    print()

    about = Bot(order="bot", order_content="")
    about()
    assert about.suborder == ""
    assert about.suborder_content == ""

    about = Bot(order="bot", order_content="about")
    about()
    assert about.suborder == "about"
    assert about.suborder_content == ""

    with pytest.raises(OrderException):
        on = Bot(order="bot", order_content="on")
        on()

    on = Bot(order="bot", order_content="on")
    on.message_chain.sender.permission = "ADMINISTRATOR"
    on()
    assert on.suborder == "enable"
    assert on.suborder_content == ""

    with pytest.raises(OrderException):
        off = Bot(order="bot", order_content="off")
        off()

    off = Bot(order="bot", order_content="off")
    off.message_chain.sender.permission = "ADMINISTRATOR"
    off()
    assert off.suborder == "disable"
    assert off.suborder_content == ""

    with pytest.raises(OrderException):
        nickname = Bot(order="bot", order_content="name Adam")
        nickname()

    nickname = Bot(order="bot", order_content="name Adam")
    nickname.message_chain.sender.permission = "ADMINISTRATOR"
    nickname()
    assert nickname.suborder == "nickname"
    assert nickname.suborder_content == "Adam"
