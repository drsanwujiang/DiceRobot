"""检定规则的加载。

规则以 JSON 文件保存在数据目录中，机器人所有者可直接编辑。内置规则在此以字典常量给出，
仅用于在文件缺失时写入一份，之后一律以文件为准。

加载在启动时完成，任何一份文件有问题都直接抛出：规则决定每一次检定的结果，带着错误的
规则运行比启动失败更难排查。
"""

from __future__ import annotations

import json
from collections.abc import Mapping
from pathlib import Path
from typing import Any

from loguru import logger
from pydantic import BaseModel, ConfigDict, Field, ValidationError

from dicerobot.errors import ConfigurationError
from dicerobot.trpg.check import CheckLevel, CheckRule, Condition, ConditionError, compile_condition

__all__ = ["DEFAULT_RULES", "MAX_SKILL_CHECKED", "load_rules"]

# 穷举验证的技能值上界
MAX_SKILL_CHECKED = 100

# 与规则文件排版保持一致
# fmt: off
DEFAULT_RULES: Mapping[str, dict[str, Any]] = {
    "coc7": {
        "id": "coc7",
        "name": "COC 7 检定规则",
        "description": "COC 7 版规则书设定的检定规则",
        "levels": [
            {
                "name": "大成功",
                "description": "骰出 1",
                "condition": "roll == 1"
            },
            {
                "name": "大失败",
                "description": "骰出 100。若技能值小于 50，则大于等于 96 的结果都是大失败",
                "condition": "roll == 100 or (roll >= 96 and skill < 50)"
            },
            {
                "name": "极难成功",
                "description": "骰值小于等于技能值的五分之一（向下取整）",
                "condition": "roll <= skill // 5"
            },
            {
                "name": "困难成功",
                "description": "骰值小于等于技能值的一半",
                "condition": "roll <= skill // 2"
            },
            {
                "name": "成功",
                "description": "骰值小于等于技能值，也称为一般成功",
                "condition": "roll <= skill"
            },
            {
                "name": "失败",
                "description": "骰值大于技能值",
                "condition": "True"
            }
        ]
    },
    "simple": {
        "id": "simple",
        "name": "简易检定规则",
        "description": "只区分成功与失败的检定规则",
        "levels": [
            {
                "name": "成功",
                "description": "骰值小于等于技能值",
                "condition": "roll <= skill"
            },
            {
                "name": "失败",
                "description": "骰值大于技能值",
                "condition": "True"
            }
        ]
    }
}
# fmt: on


class _Level(BaseModel):
    """规则文件中的一个等级。"""

    model_config = ConfigDict(extra="forbid")

    name: str = Field(min_length=1)
    description: str = ""
    condition: str = Field(min_length=1)


class _Rule(BaseModel):
    """一份规则文件。"""

    model_config = ConfigDict(extra="forbid")

    id: str = Field(min_length=1)
    name: str = Field(min_length=1)
    description: str = ""
    levels: list[_Level] = Field(min_length=1)


def load_rules(directory: Path) -> Mapping[str, CheckRule]:
    """写入缺失的内置规则，再加载目录中的全部规则。

    Args:
        directory: 规则目录，不存在时创建。

    Raises:
        ConfigurationError: 文件无法解析、标识重复、条件非法，或规则未覆盖全部取值。
    """

    _write_missing(directory)

    rules: dict[str, CheckRule] = {}

    for path in sorted(directory.glob("*.json")):
        rule = _load(path)

        if rule.id in rules:
            raise ConfigurationError(f"规则标识 {rule.id} 重复，请检查 {directory} 中的文件名")

        rules[rule.id] = rule

    if not rules:
        raise ConfigurationError(f"规则目录 {directory} 中没有任何规则")

    logger.info("已加载 {} 套检定规则：{}", len(rules), "、".join(rules))

    return rules


def _write_missing(directory: Path) -> None:
    """把缺失的内置规则写入目录。"""

    directory.mkdir(parents=True, exist_ok=True)

    for rule_id, document in DEFAULT_RULES.items():
        path = directory / f"{rule_id}.json"

        if path.exists():
            continue

        path.write_text(json.dumps(document, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        logger.info("已写入内置检定规则 {}", path)


def _load(path: Path) -> CheckRule:
    """解析一份规则文件并编译其判定条件。"""

    try:
        document = _Rule.model_validate_json(path.read_text(encoding="utf-8"))
    except (OSError, ValidationError) as e:
        raise ConfigurationError(f"规则文件 {path} 无法解析：{e}") from e

    if document.id != path.stem:
        raise ConfigurationError(f"规则文件 {path} 中的标识为 {document.id}，与文件名不一致")

    levels = tuple(
        CheckLevel(name=level.name, description=level.description, matches=_compile(path, level))
        for level in document.levels
    )
    rule = CheckRule(id=document.id, name=document.name, description=document.description, levels=levels)

    _verify(path, rule)

    return rule


def _compile(path: Path, level: _Level) -> Condition:
    try:
        return compile_condition(level.condition)
    except ConditionError as e:
        raise ConfigurationError(f"规则文件 {path} 中「{level.name}」的判定条件有问题：{e}") from e


def _verify(path: Path, rule: CheckRule) -> None:
    """穷举全部取值，确认规则可用。

    ``check`` 取首个匹配的等级，若某组取值一个等级都不匹配，故障要等玩家掷出那个点数才会
    出现。此处提前穷举一次，顺带找出永远匹配不到的等级，通常是等级顺序有误。
    """

    unreachable = {level.name for level in rule.levels}

    for skill in range(MAX_SKILL_CHECKED + 1):
        for roll in range(1, 101):
            matched = _match(path, rule, skill=skill, roll=roll)

            if matched is None:
                raise ConfigurationError(f"规则文件 {path} 未覆盖技能值 {skill}、骰值 {roll}，请补一个无条件匹配的等级")

            unreachable.discard(matched.name)

    for name in unreachable:
        logger.warning("规则 {} 的等级「{}」永远不会匹配，请检查等级顺序", rule.id, name)


def _match(path: Path, rule: CheckRule, *, skill: int, roll: int) -> CheckLevel | None:
    for level in rule.levels:
        try:
            if level.matches(skill, roll):
                return level
        except ArithmeticError as e:
            raise ConfigurationError(
                f"规则文件 {path} 中「{level.name}」的判定条件在技能值 {skill}、骰值 {roll} 时出错：{e}"
            ) from e

    return None
