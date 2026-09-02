"""技能检定规则。

一次检定即以 d100 对技能值取结果等级。规则以有序的等级列表描述，取首个匹配的等级，
因此等级的排列顺序即优先级：大成功与大失败须排在普通成功之前，否则技能值达到 100
时 100 点会被判为成功。

判定条件由规则文件以表达式给出，在加载时编译为闭包，运行时不再解析。表达式经白名单
校验，只放行算术、比较与布尔运算，变量仅 ``skill`` 与 ``roll``；函数调用、属性访问等
一律拒绝，因而无法通过规则文件执行任意代码。
"""

from __future__ import annotations

import ast
import operator
from collections.abc import Callable, Mapping
from dataclasses import dataclass
from typing import Any

__all__ = ["CheckLevel", "CheckRule", "Condition", "ConditionError", "check", "compile_condition"]

Condition = Callable[[int, int], bool]

_Operand = Callable[[int, int], Any]

_VARIABLES = ("skill", "roll")

_ARITHMETIC: Mapping[type[ast.operator], Callable[[Any, Any], Any]] = {
    ast.Add: operator.add,
    ast.Sub: operator.sub,
    ast.Mult: operator.mul,
    ast.Div: operator.truediv,
    ast.FloorDiv: operator.floordiv,
    ast.Mod: operator.mod,
}

_COMPARISONS: Mapping[type[ast.cmpop], Callable[[Any, Any], bool]] = {
    ast.Eq: operator.eq,
    ast.NotEq: operator.ne,
    ast.Lt: operator.lt,
    ast.LtE: operator.le,
    ast.Gt: operator.gt,
    ast.GtE: operator.ge,
}

_UNARY: Mapping[type[ast.unaryop], Callable[[Any], Any]] = {
    ast.UAdd: operator.pos,
    ast.USub: operator.neg,
    ast.Not: operator.not_,
}


class ConditionError(ValueError):
    """判定条件无法解析或含有不允许的写法。"""


@dataclass(frozen=True, slots=True)
class CheckLevel:
    """一个结果等级。

    Attributes:
        name: 等级名称，用于回复。
        description: 判定条件的文字说明，用于 ``.rule``。
        matches: 判定函数，入参为技能值与骰值。
    """

    name: str
    description: str
    matches: Condition


@dataclass(frozen=True, slots=True)
class CheckRule:
    """一套检定规则。"""

    id: str
    name: str
    description: str
    levels: tuple[CheckLevel, ...]


def compile_condition(expression: str) -> Condition:
    """把判定条件编译为闭包。

    在加载规则时调用一次，此后每次检定只调用返回的闭包，不再解析表达式。

    Args:
        expression: 形如 ``roll <= skill // 5`` 的表达式。

    Raises:
        ConditionError: 无法解析，或含有白名单之外的写法。
    """

    try:
        tree = ast.parse(expression, mode="eval")
    except SyntaxError as e:
        raise ConditionError(f"无法解析：{e.msg}") from e

    operand = _compile(tree.body)

    def condition(skill: int, roll: int) -> bool:
        return bool(operand(skill, roll))

    return condition


def check(rule: CheckRule, *, skill: int, roll: int) -> CheckLevel:
    """判定结果等级。

    Args:
        rule: 所用规则。
        skill: 技能值。
        roll: d100 的骰值。

    Raises:
        ValueError: 规则未覆盖该组取值。规则在加载时已穷举验证，正常不会发生。
    """

    for level in rule.levels:
        if level.matches(skill, roll):
            return level

    raise ValueError(f"规则 {rule.id} 未匹配到任何等级（技能值 {skill}，骰值 {roll}）")


def _compile(node: ast.expr) -> _Operand:
    """把一个表达式节点编译为闭包。

    只处理白名单内的节点：不在其中的一律拒绝，包括函数调用、属性访问、下标与推导式。
    """

    match node:
        case ast.Constant(value=bool() | int() as value):
            return lambda skill, roll: value

        case ast.Name(id=name) if name in _VARIABLES:
            index = _VARIABLES.index(name)

            return lambda skill, roll: (skill, roll)[index]

        case ast.UnaryOp(op=op, operand=operand) if type(op) in _UNARY:
            unary = _UNARY[type(op)]
            compiled = _compile(operand)

            return lambda skill, roll: unary(compiled(skill, roll))

        case ast.BinOp(left=left, op=op, right=right) if type(op) in _ARITHMETIC:
            arithmetic = _ARITHMETIC[type(op)]
            compiled_left = _compile(left)
            compiled_right = _compile(right)

            return lambda skill, roll: arithmetic(compiled_left(skill, roll), compiled_right(skill, roll))

        case ast.BoolOp(op=ast.And(), values=values):
            compiled_values = [_compile(value) for value in values]

            return lambda skill, roll: all(value(skill, roll) for value in compiled_values)

        case ast.BoolOp(op=ast.Or(), values=values):
            compiled_values = [_compile(value) for value in values]

            return lambda skill, roll: any(value(skill, roll) for value in compiled_values)

        case ast.Compare(left=left, ops=ops, comparators=comparators) if all(type(op) in _COMPARISONS for op in ops):
            return _compile_comparison(left, ops, comparators)

    raise ConditionError(f"不允许的写法：{ast.unparse(node)}")


def _compile_comparison(left: ast.expr, ops: list[ast.cmpop], comparators: list[ast.expr]) -> _Operand:
    """编译比较，包括 ``1 <= roll <= skill`` 这样的连比。"""

    compiled_left = _compile(left)
    steps = [(_COMPARISONS[type(op)], _compile(comparator)) for op, comparator in zip(ops, comparators, strict=True)]

    def compare(skill: int, roll: int) -> bool:
        value = compiled_left(skill, roll)

        for comparison, compiled_right in steps:
            right = compiled_right(skill, roll)

            if not comparison(value, right):
                return False

            value = right

        return True

    return compare
