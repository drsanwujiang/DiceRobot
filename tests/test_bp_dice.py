import pytest

import tests.base
from app.exceptions import OrderException
from plugins.dicerobot.order.bp_dice import BPDice


def test_bonus_dice():
    print()

    bonus_dice = BPDice(order="rb", order_content="")
    bonus_dice()
    assert bonus_dice.count == 1
    assert bonus_dice.reason == ""

    bonus_dice = BPDice(order="rb", order_content="2")
    bonus_dice()
    assert bonus_dice.count == 2
    assert bonus_dice.reason == ""

    bonus_dice = BPDice(order="rb", order_content="Reason")
    bonus_dice()
    assert bonus_dice.count == 1
    assert bonus_dice.reason == "Reason"

    bonus_dice = BPDice(order="r b", order_content="3Reason")
    bonus_dice()
    assert bonus_dice.count == 3
    assert bonus_dice.reason == "Reason"

    bonus_dice = BPDice(order="r b", order_content="4 Reason")
    bonus_dice()
    assert bonus_dice.count == 4
    assert bonus_dice.reason == "Reason"

    with pytest.raises(OrderException):
        bonus_dice = BPDice(order="rb", order_content="101")
        bonus_dice()


def test_penalty_dice():
    print()

    penalty_dice = BPDice(order="rp", order_content="")
    penalty_dice()
    assert penalty_dice.count == 1
    assert penalty_dice.reason == ""

    penalty_dice = BPDice(order="rp", order_content="2")
    penalty_dice()
    assert penalty_dice.count == 2
    assert penalty_dice.reason == ""

    penalty_dice = BPDice(order="rp", order_content="Reason")
    penalty_dice()
    assert penalty_dice.count == 1
    assert penalty_dice.reason == "Reason"

    penalty_dice = BPDice(order="r p", order_content="3Reason")
    penalty_dice()
    assert penalty_dice.count == 3
    assert penalty_dice.reason == "Reason"

    penalty_dice = BPDice(order="r p", order_content="4 Reason")
    penalty_dice()
    assert penalty_dice.count == 4
    assert penalty_dice.reason == "Reason"

    with pytest.raises(OrderException):
        penalty_dice = BPDice(order="rp", order_content="101")
        penalty_dice()
