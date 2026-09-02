"""判定条件与等级选取的测试。

规则本身不再写死在代码里，此处只覆盖引擎：条件表达式的编译与白名单，以及按顺序取首个
匹配等级的语义。规则文件的加载与内置规则的行为见 ``tests/test_rules.py``。
"""

from __future__ import annotations

import pytest

from dicerobot.trpg.check import CheckLevel, CheckRule, ConditionError, check, compile_condition


def rule(*levels: tuple[str, str]) -> CheckRule:
    return CheckRule(
        id="test",
        name="测试规则",
        description="",
        levels=tuple(
            CheckLevel(name=name, description="", matches=compile_condition(condition)) for name, condition in levels
        ),
    )


class TestCompile:
    @pytest.mark.parametrize(
        ("condition", "skill", "roll", "expected"),
        [
            ("roll == 1", 60, 1, True),
            ("roll <= skill // 5", 60, 12, True),
            ("roll <= skill // 5", 60, 13, False),
            ("roll <= skill / 2", 61, 30, True),
            ("roll == 100 or (roll >= 96 and skill < 50)", 49, 96, True),
            ("roll == 100 or (roll >= 96 and skill < 50)", 60, 96, False),
            ("1 <= roll <= skill", 60, 60, True),
            ("1 <= roll <= skill", 60, 61, False),
            ("not roll % 2", 60, 4, True),
            ("True", 0, 1, True),
        ],
    )
    def test_evaluates(self, condition: str, skill: int, roll: int, expected: bool) -> None:
        assert compile_condition(condition)(skill, roll) is expected

    @pytest.mark.parametrize(
        "condition",
        [
            "__import__('os').system('echo')",
            "skill.__class__",
            "open('x')",
            "[roll for roll in (1, 2)]",
            "'abc' * 3",
            "bonus + 1",
            "skill if roll else 0",
            "lambda: 1",
        ],
    )
    def test_rejects_anything_outside_the_whitelist(self, condition: str) -> None:
        """规则文件可能来自他处，条件表达式不得成为执行任意代码的入口。"""

        with pytest.raises(ConditionError):
            compile_condition(condition)

    def test_reports_a_syntax_error(self) -> None:
        with pytest.raises(ConditionError, match="无法解析"):
            compile_condition("roll <=")

    def test_variables_are_bound_in_order(self) -> None:
        """skill 与 roll 不可颠倒，否则整套规则都会静默出错。"""

        assert compile_condition("skill == 7 and roll == 9")(7, 9) is True
        assert compile_condition("skill == 9 and roll == 7")(7, 9) is False


class TestCheck:
    def test_takes_the_first_matching_level(self) -> None:
        """等级按顺序判定，故特例必须排在通例之前。"""

        checked = rule(("大成功", "roll == 1"), ("成功", "roll <= skill"), ("失败", "True"))

        assert check(checked, skill=60, roll=1).name == "大成功"
        assert check(checked, skill=60, roll=2).name == "成功"
        assert check(checked, skill=60, roll=61).name == "失败"

    def test_reports_an_uncovered_combination(self) -> None:
        """规则在加载时已穷举验证，走到这里说明规则未经加载器校验。"""

        checked = rule(("成功", "roll <= skill"))

        with pytest.raises(ValueError, match="未匹配到任何等级"):
            check(checked, skill=10, roll=50)
