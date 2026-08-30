"""掷骰引擎的测试。

引擎为纯函数且随机源可注入，因此可用固定种子做精确断言，并以性质测试覆盖大量随机
输入。
"""

from __future__ import annotations

import random
from contextlib import suppress

import pytest
from hypothesis import given
from hypothesis import strategies as st

from dicerobot.trpg.dice import (
    DiceEvaluationError,
    DiceLimitError,
    DiceSyntaxError,
    Limits,
    evaluate,
    parse,
)


def roll(expression: str, *, seed: int = 42, limits: Limits | None = None) -> str:
    return str(evaluate(parse(expression), rng=random.Random(seed), limits=limits))


def value(expression: str, *, seed: int = 42, limits: Limits | None = None) -> int:
    return evaluate(parse(expression), rng=random.Random(seed), limits=limits).value


class TestBasicRolls:
    def test_bare_d_uses_the_default_surface(self) -> None:
        assert roll("d").startswith("D100=")

    def test_default_surface_is_configurable(self) -> None:
        assert roll("d", limits=Limits(default_surface=20)).startswith("D20=")

    def test_single_die_collapses_repeated_segments(self) -> None:
        """单颗骰子时后三段重合，应折叠为 `D100=57`。"""

        assert roll("d100").count("=") == 1

    def test_multiple_dice_show_every_face(self) -> None:
        assert roll("3d6+2", seed=42) == "3D6+2=(6+1+1)+2=8+2=10"

    def test_count_of_one_is_omitted_from_the_expression(self) -> None:
        assert roll("1d20").startswith("D20=")

    def test_is_case_insensitive(self) -> None:
        assert roll("3D6", seed=1) == roll("3d6", seed=1)


class TestKeep:
    def test_keeps_the_highest_dice(self) -> None:
        result = evaluate(parse("4d6k3"), rng=random.Random(42))

        assert result.expression == "4D6K3"
        assert result.detailed == "(6+6+1)"
        assert result.value == 13

    def test_keeps_the_lowest_dice(self) -> None:
        result = evaluate(parse("4d6kl2"), rng=random.Random(42))

        assert result.expression == "4D6KL2"
        assert result.value == 2

    def test_bare_k_keeps_one(self) -> None:
        result = evaluate(parse("4d6k"), rng=random.Random(42))

        assert result.expression == "4D6K1"
        assert result.value == 6

    def test_rejects_keeping_more_than_rolled(self) -> None:
        with pytest.raises(DiceLimitError, match="不能超过骰子总数"):
            value("2d6k5")

    def test_rejects_non_positive_keep(self) -> None:
        with pytest.raises(DiceLimitError, match="必须是正数"):
            value("4d6k0")


class TestArithmetic:
    """以下行为均需完整文法支持，正则切分无法实现。"""

    def test_supports_nested_parentheses(self) -> None:
        assert value("(2+3)*4") == 20

    def test_respects_operator_precedence(self) -> None:
        assert value("2+3*4") == 14

    def test_supports_unary_minus(self) -> None:
        assert value("-3+10") == 7

    def test_division_floors_to_an_integer(self) -> None:
        """全程整数运算，除法向下取整。"""

        assert value("7/2") == 3

    def test_rejects_division_by_zero(self) -> None:
        with pytest.raises(DiceEvaluationError, match="不能除以零"):
            value("1/0")

    def test_dice_can_appear_on_both_sides(self) -> None:
        assert evaluate(parse("2d10-1d6"), rng=random.Random(42)).expression == "2D10-D6"


class TestRendering:
    def test_preserves_parentheses_where_precedence_requires(self) -> None:
        assert evaluate(parse("(2+3)*4"), rng=random.Random(1)).expression == "(2+3)×4"

    def test_preserves_parentheses_on_a_non_associative_right_operand(self) -> None:
        """`1-(2-3)` 与 `1-2-3` 不等价，括号需保留。"""

        result = evaluate(parse("10-(2-3)"), rng=random.Random(1))

        assert result.expression == "10-(2-3)"
        assert result.value == 11

    def test_omits_redundant_parentheses(self) -> None:
        assert evaluate(parse("(2+3)+4"), rng=random.Random(1)).expression == "2+3+4"

    def test_uses_display_symbols_for_multiplication_and_division(self) -> None:
        assert evaluate(parse("2*3"), rng=random.Random(1)).expression == "2×3"
        assert evaluate(parse("6/3"), rng=random.Random(1)).expression == "6÷3"


class TestInputNormalization:
    @pytest.mark.parametrize("expression", ["3d6x2", "3D6X2", "3d6×2", "3d6*2"])
    def test_accepts_every_spelling_of_multiplication(self, expression: str) -> None:
        assert value(expression, seed=1) == value("3d6*2", seed=1)

    def test_accepts_full_width_parentheses(self) -> None:
        assert value("（2+3）*4") == 20

    def test_ignores_whitespace(self) -> None:
        assert value(" 3 d 6 + 2 ", seed=1) == value("3d6+2", seed=1)

    def test_accepts_full_width_digits(self) -> None:
        assert value("２＋３") == 5

    def test_rejects_digit_like_characters_that_are_not_numbers(self) -> None:
        """`²` 的 isdigit 为真但 int() 无法转换，词法分析须按 isdecimal 判断。

        用例源自性质测试发现的反例，保留以防回归。
        """

        with pytest.raises(DiceSyntaxError, match="无法识别的字符"):
            parse("²")


class TestSyntaxErrors:
    """语法错误须给出出错位置。"""

    @pytest.mark.parametrize(
        ("expression", "position"),
        [
            ("1+", 2),
            ("(1+2", 4),
            ("1+2)", 3),
            ("1@2", 1),
            ("*3", 0),
        ],
    )
    def test_reports_the_offending_position(self, expression: str, position: int) -> None:
        with pytest.raises(DiceSyntaxError) as info:
            parse(expression)

        assert info.value.position == position

    def test_rejects_an_empty_expression(self) -> None:
        with pytest.raises(DiceSyntaxError, match="表达式为空"):
            parse("   ")

    def test_rejects_an_overlong_expression(self) -> None:
        with pytest.raises(DiceSyntaxError, match="过长"):
            parse("1+" * 200)


class TestLimits:
    def test_rejects_too_many_dice(self) -> None:
        with pytest.raises(DiceLimitError, match="最多掷"):
            value("200d6", limits=Limits(max_count=100))

    def test_rejects_too_many_surfaces(self) -> None:
        with pytest.raises(DiceLimitError, match="最多"):
            value("d99999", limits=Limits(max_surface=1000))

    def test_rejects_non_positive_count(self) -> None:
        with pytest.raises(DiceLimitError, match="必须是正数"):
            value("0d6")

    def test_total_across_the_expression_is_capped(self) -> None:
        """单组上限可用加法绕开，故需限制总量。"""

        limits = Limits(max_count=100, max_total_dice=150)

        value("100d6", limits=limits)

        with pytest.raises(DiceLimitError, match="整个表达式"):
            value("100d6+100d6", limits=limits)


class TestReevaluation:
    def test_the_same_tree_rerolls_each_time(self) -> None:
        """`.r 1d100#10` 即解析一次、求值十次。"""

        node = parse("d100")
        rng = random.Random(42)
        values = [evaluate(node, rng=rng).value for _ in range(10)]

        assert len(set(values)) > 1

    def test_the_same_seed_reproduces_the_same_roll(self) -> None:
        assert roll("10d10", seed=7) == roll("10d10", seed=7)


class TestProperties:
    @given(
        count=st.integers(min_value=1, max_value=50),
        surface=st.integers(min_value=1, max_value=1000),
        seed=st.integers(),
    )
    def test_sum_always_lies_within_the_possible_range(self, count: int, surface: int, seed: int) -> None:
        result = evaluate(parse(f"{count}d{surface}"), rng=random.Random(seed))

        assert count <= result.value <= count * surface

    @given(
        count=st.integers(min_value=2, max_value=30),
        keep=st.integers(min_value=1, max_value=2),
        seed=st.integers(),
    )
    def test_keeping_the_highest_never_scores_below_keeping_the_lowest(self, count: int, keep: int, seed: int) -> None:
        node_high = parse(f"{count}d20k{keep}")
        node_low = parse(f"{count}d20kl{keep}")

        # 同一个种子让两次掷骰的点数序列一致，差别只来自保留策略。
        high = evaluate(node_high, rng=random.Random(seed)).value
        low = evaluate(node_low, rng=random.Random(seed)).value

        assert high >= low

    @given(seed=st.integers())
    def test_rendered_result_always_ends_with_the_value(self, seed: int) -> None:
        result = evaluate(parse("3d6+2"), rng=random.Random(seed))

        assert str(result).endswith(f"={result.value}")

    @given(expression=st.text(max_size=30))
    def test_never_raises_anything_outside_the_declared_errors(self, expression: str) -> None:
        """任意输入只应产生掷骰异常，不应逸出 ValueError、IndexError 等。"""

        with suppress(DiceSyntaxError, DiceLimitError, DiceEvaluationError):
            evaluate(parse(expression), rng=random.Random(0))
