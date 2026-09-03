"""结果的文本呈现。

掷骰结果分四段逐步展开：规范化表达式、每颗骰子的点数、骰子求和后的算式、最终值，
如 ``3D6+2=(4+2+5)+2=11+2=13``。单颗骰子时后三段重合，故拼装时折叠相邻的重复段。
"""

from __future__ import annotations

from dicerobot.trpg.dice.ast import BinaryOperator

__all__ = [
    "ATOM_PRECEDENCE",
    "PRECEDENCE",
    "SYMBOLS",
    "UNARY_PRECEDENCE",
    "parenthesize",
    "render_result",
]

PRECEDENCE: dict[str, int] = {"+": 1, "-": 1, "*": 2, "/": 2, "^": 3}
UNARY_PRECEDENCE = 4
ATOM_PRECEDENCE = 5

# 显示用的运算符，乘除采用全角形式以贴近手写算式。
SYMBOLS: dict[BinaryOperator, str] = {"+": "+", "-": "-", "*": "×", "/": "÷", "^": "^"}

_RIGHT_ASSOCIATIVE = frozenset({"^"})
_NON_ASSOCIATIVE = frozenset({"-", "/"})


def parenthesize(
    text: str,
    *,
    precedence: int,
    parent: int,
    right_operand: bool = False,
    parent_operator: str = "",
) -> str:
    """按运算优先级决定是否给子式加括号。

    优先级更低时一律需要括号。优先级相同时取决于结合性：减与除的右操作数需要括号
    （``1-(2-3)`` 与 ``1-2-3`` 不等价），右结合的乘方相反，需要括号的是左操作数
    （``(2^3)^2`` 与 ``2^3^2`` 不等价）。
    """

    if precedence < parent:
        return f"({text})"

    if precedence == parent:
        if right_operand and parent_operator in _NON_ASSOCIATIVE:
            return f"({text})"

        if not right_operand and parent_operator in _RIGHT_ASSOCIATIVE:
            return f"({text})"

    return text


def render_result(*, expression: str, detailed: str, brief: str, value: int) -> str:
    """把四段拼接为最终输出，并折叠相邻的重复段。"""

    segments = [expression, detailed, brief, str(value)]
    collapsed = [segments[0]]

    for segment in segments[1:]:
        if segment != collapsed[-1]:
            collapsed.append(segment)

    return "=".join(collapsed)
