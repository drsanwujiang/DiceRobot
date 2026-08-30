"""检定规则的测试。"""

from __future__ import annotations

import pytest

from dicerobot.trpg.check import RULES, check, get_rule


def level(skill: int, roll: int) -> str:
    rule = get_rule("coc7")
    assert rule is not None

    return check(rule, skill=skill, roll=roll).name


class TestCoc7:
    @pytest.mark.parametrize(
        ("skill", "roll", "expected"),
        [
            (60, 1, "大成功"),
            (60, 12, "极难成功"),
            (60, 13, "困难成功"),
            (60, 30, "困难成功"),
            (60, 31, "成功"),
            (60, 60, "成功"),
            (60, 61, "失败"),
            (60, 95, "失败"),
            (60, 100, "大失败"),
        ],
    )
    def test_levels(self, skill: int, roll: int, expected: str) -> None:
        assert level(skill, roll) == expected

    @pytest.mark.parametrize("roll", [96, 97, 98, 99, 100])
    def test_low_skill_fumbles_from_96(self, roll: int) -> None:
        """技能值低于 50 时，96 及以上均为大失败。"""

        assert level(49, roll) == "大失败"

    @pytest.mark.parametrize("roll", [96, 97, 98, 99])
    def test_high_skill_fumbles_only_on_100(self, roll: int) -> None:
        assert level(50, roll) == "失败"
        assert level(50, 100) == "大失败"

    def test_hundred_is_a_fumble_even_when_skill_is_maxed(self) -> None:
        """技能值达到 100 时 100 点仍是大失败，因此大失败须排在成功之前判定。"""

        assert level(100, 100) == "大失败"

    def test_one_is_a_critical_even_when_skill_is_zero(self) -> None:
        assert level(0, 1) == "大成功"


class TestSimpleRule:
    def test_only_distinguishes_success_and_failure(self) -> None:
        rule = get_rule("simple")
        assert rule is not None

        assert check(rule, skill=60, roll=1).name == "成功"
        assert check(rule, skill=60, roll=60).name == "成功"
        assert check(rule, skill=60, roll=61).name == "失败"


class TestLookup:
    def test_is_case_insensitive(self) -> None:
        assert get_rule("COC7") is not None

    def test_unknown_rule_returns_nothing(self) -> None:
        assert get_rule("nonexistent") is None

    @pytest.mark.parametrize("rule_id", list(RULES))
    def test_every_rule_covers_all_inputs(self, rule_id: str) -> None:
        """最后一级须无条件匹配，否则会有取值组合判不出结果。"""

        rule = RULES[rule_id]

        for skill in (0, 1, 49, 50, 99, 100, 200):
            for roll in range(1, 101):
                check(rule, skill=skill, roll=roll)
