"""掷骰引擎。

解析与求值分离：``parse`` 得到的语法树可反复求值，每次重新掷骰。

    >>> import random
    >>> from dicerobot.trpg.dice import evaluate, parse
    >>> str(evaluate(parse("3d6+2"), rng=random.Random(42)))
    '3D6+2=(6+1+1)+2=8+2=10'

本包不做任何 IO，随机源由调用方注入，可脱离平台独立开发与测试。
"""

from __future__ import annotations

from dicerobot.trpg.dice.ast import Binary, Dice, Node, Number, Unary
from dicerobot.trpg.dice.errors import DiceError, DiceEvaluationError, DiceLimitError, DiceSyntaxError
from dicerobot.trpg.dice.evaluator import Limits, RollResult, evaluate
from dicerobot.trpg.dice.parser import MAX_EXPRESSION_LENGTH, parse
from dicerobot.trpg.dice.render import render_result

__all__ = [
    "MAX_EXPRESSION_LENGTH",
    "Binary",
    "Dice",
    "DiceError",
    "DiceEvaluationError",
    "DiceLimitError",
    "DiceSyntaxError",
    "Limits",
    "Node",
    "Number",
    "RollResult",
    "Unary",
    "evaluate",
    "parse",
    "render_result",
]
