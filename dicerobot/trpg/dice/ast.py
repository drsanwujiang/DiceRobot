"""掷骰表达式的语法树。

节点均不可变，只描述结构而不含求值结果，因此同一棵树可反复求值。

"""

from __future__ import annotations

from dataclasses import dataclass
from typing import Literal

__all__ = ["Binary", "BinaryOperator", "Dice", "Node", "Number", "Unary", "UnaryOperator"]

type BinaryOperator = Literal["+", "-", "*", "/"]
type UnaryOperator = Literal["+", "-"]


@dataclass(frozen=True, slots=True)
class Number:
    """字面量。"""

    value: int


@dataclass(frozen=True, slots=True)
class Dice:
    """一组骰子。

    Attributes:
        count: 骰子个数。``None`` 表示未写明，按 1 处理。
        surface: 骰子面数。``None`` 表示未写明，按会话的默认面数处理。
        keep: 保留的骰子个数。``None`` 表示全部计入。
        keep_lowest: 保留最低的若干颗（``kl``）而非最高的若干颗（``k``）。
    """

    count: int | None
    surface: int | None
    keep: int | None
    keep_lowest: bool


@dataclass(frozen=True, slots=True)
class Unary:
    """一元运算。"""

    operator: UnaryOperator
    operand: Node


@dataclass(frozen=True, slots=True)
class Binary:
    """二元运算。"""

    operator: BinaryOperator
    left: Node
    right: Node


type Node = Number | Dice | Unary | Binary
