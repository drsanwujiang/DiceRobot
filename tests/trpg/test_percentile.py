"""十位骰与个位骰的测试。

取舍必须在组合成结果之后进行：个位为 0 时十位 0 代表 100，改比十位数字会让奖励骰取到
最差的结果。这条只在个位恰为 0 时暴露，故用脚本化的随机源逐一钉死。
"""

from __future__ import annotations

import random
from collections.abc import Iterable

from hypothesis import given
from hypothesis import strategies as st

from dicerobot.trpg.percentile import PercentileRoll, combine, roll_percentile


class ScriptedRandom(random.Random):
    """按给定顺序给出点数的随机源。先取个位骰，再依次取各个十位骰。"""

    def __init__(self, values: Iterable[int]) -> None:
        super().__init__()

        self._values = list(values)

    def randint(self, a: int, b: int) -> int:
        return self._values.pop(0)


class TestCombine:
    def test_double_zero_is_one_hundred(self) -> None:
        assert combine(0, 0) == 100

    def test_zero_tens_with_nonzero_units_is_a_single_digit(self) -> None:
        """十位 0 只在个位也是 0 时记 100，否则是 01 到 09。"""

        assert combine(0, 5) == 5

    def test_combines_tens_and_units(self) -> None:
        assert combine(5, 2) == 52
        assert combine(9, 0) == 90


class TestSelection:
    def test_bonus_takes_the_lower_result(self) -> None:
        roll = roll_percentile(rng=ScriptedRandom([2, 5, 3]), extra=1)

        assert roll.base == 52
        assert roll.value == 32

    def test_penalty_takes_the_higher_result(self) -> None:
        roll = roll_percentile(rng=ScriptedRandom([2, 5, 3]), extra=1, penalty=True)

        assert roll.value == 52

    def test_bonus_avoids_one_hundred_when_units_are_zero(self) -> None:
        """个位 0、十位掷出 0 与 9：候选是 100 与 90，奖励骰取 90。

        若改比十位数字，0 最小，反而会取到 100——这正是奖励骰最容易写错的地方。
        """

        roll = roll_percentile(rng=ScriptedRandom([0, 0, 9]), extra=1)

        assert roll.base == 100
        assert roll.value == 90

    def test_penalty_takes_one_hundred_when_units_are_zero(self) -> None:
        roll = roll_percentile(rng=ScriptedRandom([0, 9, 0]), extra=1, penalty=True)

        assert roll.base == 90
        assert roll.value == 100

    def test_zero_tens_with_nonzero_units_is_the_best_result(self) -> None:
        """十位 0、个位 5 的结果是 05，奖励骰应当取它。"""

        roll = roll_percentile(rng=ScriptedRandom([5, 7, 0]), extra=1)

        assert roll.value == 5

    def test_without_extra_dice_the_result_is_the_base(self) -> None:
        roll = roll_percentile(rng=ScriptedRandom([3, 4]))

        assert roll.tens == (4,)
        assert roll.value == 43 == roll.base


class TestRendering:
    def test_expands_the_extra_tens_dice(self) -> None:
        roll = roll_percentile(rng=ScriptedRandom([2, 5, 3, 6, 9]), extra=3)

        assert str(roll) == "52[奖励骰:3 6 9]"

    def test_labels_penalty_dice(self) -> None:
        roll = roll_percentile(rng=ScriptedRandom([2, 5, 3]), extra=1, penalty=True)

        assert str(roll) == "52[惩罚骰:3]"

    def test_collapses_to_the_value_without_extra_dice(self) -> None:
        assert str(roll_percentile(rng=ScriptedRandom([2, 5]))) == "52"


class TestProperties:
    @given(
        extra=st.integers(min_value=0, max_value=5),
        penalty=st.booleans(),
        seed=st.integers(),
    )
    def test_result_always_lies_within_the_possible_range(self, extra: int, penalty: bool, seed: int) -> None:
        roll = roll_percentile(rng=random.Random(seed), extra=extra, penalty=penalty)

        assert 1 <= roll.value <= 100

    @given(extra=st.integers(min_value=1, max_value=5), seed=st.integers())
    def test_bonus_never_scores_above_the_base(self, extra: int, seed: int) -> None:
        """同一个种子给出同一组骰，差别只来自取舍方向。"""

        bonus = roll_percentile(rng=random.Random(seed), extra=extra)
        penalty = roll_percentile(rng=random.Random(seed), extra=extra, penalty=True)

        assert bonus.value <= bonus.base <= penalty.value

    @given(extra=st.integers(min_value=1, max_value=5), seed=st.integers())
    def test_the_chosen_result_comes_from_the_dice_that_were_rolled(self, extra: int, seed: int) -> None:
        roll: PercentileRoll = roll_percentile(rng=random.Random(seed), extra=extra)

        assert roll.value in {combine(tens, roll.units) for tens in roll.tens}
        assert len(roll.tens) == extra + 1
