import pytest

import tests.base
from app.exceptions import OrderException
from plugins.dicerobot.order.dice import Dice


def test_dice():
    print()

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

    dice = Dice(order="r", order_content="(5d100+d30+666)*5-2+6d50k2x2+6X5 Some Reason")
    dice()
    assert dice.expression == "(5D100+D30+666)×5-2+6D50K2×2+6×5"
    assert dice.reason == "Some Reason"

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
