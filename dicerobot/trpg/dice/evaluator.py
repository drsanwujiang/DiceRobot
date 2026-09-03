"""语法树的求值。

一次遍历同时算出数值与三段呈现文本：骰子的点数明细在掷出时才存在，而算式结构来自
语法树，两者需同步生成。

随机数发生器由调用方注入，使求值可复现并便于性质测试。
"""

from __future__ import annotations

import random
from dataclasses import dataclass

from dicerobot.trpg.dice.ast import Binary, BinaryOperator, Dice, Node, Number, Unary, UnaryOperator
from dicerobot.trpg.dice.errors import DiceEvaluationError, DiceLimitError
from dicerobot.trpg.dice.render import (
    ATOM_PRECEDENCE,
    PRECEDENCE,
    SYMBOLS,
    UNARY_PRECEDENCE,
    parenthesize,
    render_result,
)
from dicerobot.trpg.percentile import roll_percentile

__all__ = ["Limits", "RollResult", "evaluate"]

_PERCENTILE_SURFACE = 100
"""奖惩骰的面数。这类骰子以十位骰与个位骰定义，面数不是可选项。"""


@dataclass(frozen=True, slots=True)
class Limits:
    """求值时的取值范围。

    Attributes:
        default_surface: 未写明面数时使用的面数。奖惩骰固定 100，不受此项影响。
        max_count: 单组骰子的最大个数。
        max_surface: 骰子的最大面数。
        max_total_dice: 整个表达式中骰子个数的总和上限。
        max_extra: 单组骰子可追加的奖惩骰个数上限。
        max_exponent: 乘方的最大指数。
        max_power_bits: 乘方结果的规模上限，以二进制位数计。
    """

    default_surface: int = 100
    max_count: int = 100
    max_surface: int = 1000
    max_total_dice: int = 500
    max_extra: int = 10
    max_exponent: int = 64
    max_power_bits: int = 64


@dataclass(frozen=True, slots=True)
class RollResult:
    """一次求值的结果。"""

    value: int

    expression: str
    """规范化后的表达式，未写明的面数已填入实际值，如 ``D100``。"""

    detailed: str
    """展开到每颗骰子点数的算式，如 ``(4+2+5)+2``。"""

    brief: str
    """骰子求和之后的算式，如 ``11+2``。"""

    def __str__(self) -> str:
        return render_result(
            expression=self.expression,
            detailed=self.detailed,
            brief=self.brief,
            value=self.value,
        )


@dataclass(frozen=True, slots=True)
class _Evaluated:
    """子式的求值结果。``precedence`` 用于决定拼接时是否需要括号。"""

    value: int
    expression: str
    detailed: str
    brief: str
    precedence: int


def evaluate(node: Node, *, rng: random.Random, limits: Limits | None = None) -> RollResult:
    """求值。

    同一棵语法树可反复求值，每次重新掷骰，``.r 1d100#10`` 即解析一次、求值十次。

    Raises:
        DiceLimitError: 骰子个数、面数或保留数量越界。
        DiceEvaluationError: 除以零。
    """

    result = _Evaluator(rng=rng, limits=limits if limits is not None else Limits()).visit(node)

    return RollResult(
        value=result.value,
        expression=result.expression,
        detailed=result.detailed,
        brief=result.brief,
    )


class _Evaluator:
    def __init__(self, *, rng: random.Random, limits: Limits) -> None:
        self._rng = rng
        self._limits = limits
        self._total_dice = 0

    def visit(self, node: Node) -> _Evaluated:
        match node:
            case Number(value=value):
                text = str(value)

                return _Evaluated(value, text, text, text, ATOM_PRECEDENCE)

            case Dice():
                return self._visit_dice(node)

            case Unary(operator=operator, operand=operand):
                return self._visit_unary(operator, operand)

            case Binary(operator=binary_operator, left=left, right=right):
                return self._visit_binary(binary_operator, left, right)

    def _visit_dice(self, node: Dice) -> _Evaluated:
        count = node.count if node.count is not None else 1

        if node.extra is not None:
            return self._visit_percentile(node, count=count, extra=node.extra)

        surface = node.surface if node.surface is not None else self._limits.default_surface

        self._check(count=count, surface=surface, keep=node.keep)

        faces = [self._rng.randint(1, surface) for _ in range(count)]
        kept = self._keep(faces, node)
        total = sum(kept)

        # 明细自带括号，可视为原子，拼接时无需再嵌套一层。
        joined = "+".join(str(face) for face in kept)
        detailed = f"({joined})" if len(kept) > 1 else str(total)

        return _Evaluated(total, self._expression(node, count, surface), detailed, str(total), ATOM_PRECEDENCE)

    def _visit_percentile(self, node: Dice, *, count: int, extra: int) -> _Evaluated:
        """求值一组带奖惩骰的 d100。

        面数不接受 100 之外的取值：改面数无从解释，而静默忽略会让 ``2d20b1`` 看起来
        掷的是 d20。
        """

        if node.surface is not None and node.surface != _PERCENTILE_SURFACE:
            raise DiceLimitError(f"奖惩骰只能是 D{_PERCENTILE_SURFACE}，面数请留空或写 {_PERCENTILE_SURFACE}")

        self._check(count=count, surface=_PERCENTILE_SURFACE, keep=node.keep, extra=extra)

        rolls = [roll_percentile(rng=self._rng, extra=extra, penalty=node.penalty) for _ in range(count)]
        kept = self._keep([roll.value for roll in rolls], node)
        total = sum(kept)

        # 只有一颗时展开十位骰，多颗逐一展开会使回复过长。
        detailed = str(rolls[0]) if count == 1 else f"({'+'.join(str(face) for face in kept)})"

        return _Evaluated(
            total, self._expression(node, count, _PERCENTILE_SURFACE), detailed, str(total), ATOM_PRECEDENCE
        )

    @staticmethod
    def _expression(node: Dice, count: int, surface: int) -> str:
        """规范化后的表达式，参数顺序与文法一致。"""

        text = f"{count if count > 1 else ''}D{surface}"

        if node.keep is not None:
            text += f"{'Q' if node.keep_lowest else 'K'}{node.keep}"

        if node.extra is not None:
            text += f"{'P' if node.penalty else 'B'}{node.extra if node.extra > 1 else ''}"

        return text

    def _check(self, *, count: int, surface: int, keep: int | None, extra: int | None = None) -> None:
        if count <= 0:
            raise DiceLimitError("骰子个数必须是正数")

        if surface <= 0:
            raise DiceLimitError("骰子面数必须是正数")

        if count > self._limits.max_count:
            raise DiceLimitError(f"一次最多掷 {self._limits.max_count} 颗骰子")

        if surface > self._limits.max_surface:
            raise DiceLimitError(f"骰子最多 {self._limits.max_surface} 面")

        self._total_dice += count

        if self._total_dice > self._limits.max_total_dice:
            # 单组上限可用加法绕开，故需限制整个表达式的总量。
            raise DiceLimitError(f"整个表达式最多掷 {self._limits.max_total_dice} 颗骰子")

        if keep is not None:
            if keep <= 0:
                raise DiceLimitError("保留的骰子个数必须是正数")

            if keep > count:
                raise DiceLimitError("保留的骰子个数不能超过骰子总数")

        if extra is not None:
            if extra <= 0:
                raise DiceLimitError("奖惩骰的个数必须是正数")

            if extra > self._limits.max_extra:
                raise DiceLimitError(f"最多追加 {self._limits.max_extra} 个奖惩骰")

    @staticmethod
    def _keep(faces: list[int], node: Dice) -> list[int]:
        if node.keep is None:
            return faces

        if node.keep_lowest:
            return sorted(faces)[: node.keep]

        return sorted(faces, reverse=True)[: node.keep]

    def _visit_unary(self, operator: UnaryOperator, operand: Node) -> _Evaluated:
        operand_result = self.visit(operand)

        def render(text: str) -> str:
            return operator + parenthesize(text, precedence=operand_result.precedence, parent=UNARY_PRECEDENCE)

        return _Evaluated(
            -operand_result.value if operator == "-" else operand_result.value,
            render(operand_result.expression),
            render(operand_result.detailed),
            render(operand_result.brief),
            UNARY_PRECEDENCE,
        )

    def _visit_binary(self, operator: BinaryOperator, left: Node, right: Node) -> _Evaluated:
        left_result = self.visit(left)
        right_result = self.visit(right)
        precedence = PRECEDENCE[operator]
        symbol = SYMBOLS[operator]

        def render(left_text: str, right_text: str) -> str:
            rendered_left = parenthesize(
                left_text,
                precedence=left_result.precedence,
                parent=precedence,
                parent_operator=operator,
            )
            rendered_right = parenthesize(
                right_text,
                precedence=right_result.precedence,
                parent=precedence,
                right_operand=True,
                parent_operator=operator,
            )

            return rendered_left + symbol + rendered_right

        return _Evaluated(
            self._apply(operator, left_result.value, right_result.value),
            render(left_result.expression, right_result.expression),
            render(left_result.detailed, right_result.detailed),
            render(left_result.brief, right_result.brief),
            precedence,
        )

    def _apply(self, operator: BinaryOperator, left: int, right: int) -> int:
        match operator:
            case "+":
                return left + right
            case "-":
                return left - right
            case "*":
                return left * right
            case "/":
                if right == 0:
                    raise DiceEvaluationError("不能除以零")

                # 全程整数运算，除法向下取整。
                return left // right
            case "^":
                return self._power(left, right)

    def _power(self, base: int, exponent: int) -> int:
        """乘方。

        规模必须在计算之前判断：先算再检查会在 ``9^9^9`` 这类输入上耗尽内存。以底数的
        位数乘指数估算结果位数，估计偏保守。
        """

        if exponent < 0:
            # 全程整数运算，负指数得不到整数结果。
            raise DiceEvaluationError("指数不能是负数")

        if exponent > self._limits.max_exponent:
            raise DiceLimitError(f"指数最大 {self._limits.max_exponent}")

        if (abs(base).bit_length() - 1) * exponent > self._limits.max_power_bits:
            raise DiceLimitError("乘方的结果过大")

        # 指数已确认非负，结果必为整数；标准库在指数可能为负时把返回类型标为 Any。
        return int(base**exponent)
