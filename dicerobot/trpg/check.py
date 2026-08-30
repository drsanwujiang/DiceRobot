"""技能检定规则。

一次检定即以 d100 对技能值取结果等级。规则以有序的等级列表描述，取首个匹配的等级，
因此等级的排列顺序即优先级：大成功与大失败须排在普通成功之前，否则技能值达到 100
时 100 点会被判为成功。
"""

from __future__ import annotations

from collections.abc import Callable, Mapping
from dataclasses import dataclass

__all__ = ["DEFAULT_RULE_ID", "RULES", "CheckLevel", "CheckRule", "check", "get_rule"]

DEFAULT_RULE_ID = "coc7"


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
    matches: Callable[[int, int], bool]


@dataclass(frozen=True, slots=True)
class CheckRule:
    """一套检定规则。"""

    id: str
    name: str
    description: str
    levels: tuple[CheckLevel, ...]


# 《克苏鲁的呼唤》第七版规则：1 为大成功；100 必为大失败，技能值低于 50 时 96 起即为
# 大失败；其余按技能值的五分之一、二分之一划分极难与困难成功。
_COC7 = CheckRule(
    id="coc7",
    name="COC 第七版",
    description="《克苏鲁的呼唤》第七版的技能检定规则",
    levels=(
        CheckLevel("大成功", "骰出 1", lambda skill, roll: roll == 1),
        CheckLevel(
            "大失败",
            "骰出 100；技能值低于 50 时，骰出 96 及以上",
            lambda skill, roll: roll == 100 or (roll >= 96 and skill < 50),
        ),
        CheckLevel("极难成功", "骰值不大于技能值的五分之一", lambda skill, roll: roll <= skill // 5),
        CheckLevel("困难成功", "骰值不大于技能值的二分之一", lambda skill, roll: roll <= skill // 2),
        CheckLevel("成功", "骰值不大于技能值", lambda skill, roll: roll <= skill),
        CheckLevel("失败", "其余情况", lambda skill, roll: True),
    ),
)

_SIMPLE = CheckRule(
    id="simple",
    name="简单判定",
    description="只区分成功与失败，不划分成功等级",
    levels=(
        CheckLevel("成功", "骰值不大于技能值", lambda skill, roll: roll <= skill),
        CheckLevel("失败", "其余情况", lambda skill, roll: True),
    ),
)

RULES: Mapping[str, CheckRule] = {rule.id: rule for rule in (_COC7, _SIMPLE)}


def get_rule(rule_id: str) -> CheckRule | None:
    """按标识取规则，不存在时返回 ``None``。"""

    return RULES.get(rule_id.lower())


def check(rule: CheckRule, *, skill: int, roll: int) -> CheckLevel:
    """判定结果等级。

    Args:
        rule: 所用规则。
        skill: 技能值。
        roll: d100 的骰值。

    Raises:
        ValueError: 规则未覆盖该组取值。规则的最后一级应当无条件匹配。
    """

    for level in rule.levels:
        if level.matches(skill, roll):
            return level

    raise ValueError(f"规则 {rule.id} 未匹配到任何等级（技能值 {skill}，骰值 {roll}）")
