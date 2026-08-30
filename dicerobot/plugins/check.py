"""技能检定插件。"""

from __future__ import annotations

import random
import re

from pydantic import BaseModel, Field

from dicerobot.bot.context import CommandContext
from dicerobot.bot.plugin import Plugin
from dicerobot.errors import CommandError
from dicerobot.trpg.check import DEFAULT_RULE_ID, RULES, CheckRule, check, get_rule

__all__ = ["CheckChatSettings", "plugin"]

MAX_REPETITIONS = 30
MAX_SKILL = 9999

_RNG: random.Random = random.SystemRandom()

# 技能值写在最前，其余部分为检定理由。
_ARGUMENTS_PATTERN = re.compile(r"^(?P<skill>\d{1,4})?\s*(?P<reason>[\s\S]*)$")


class CheckChatSettings(BaseModel):
    """技能检定在某个会话中的设置。"""

    rule: str = Field(default=DEFAULT_RULE_ID)


plugin = Plugin(
    name="check",
    display_name="技能检定",
    description="按检定规则以 d100 判定技能",
    version="1.0.0",
    chat_settings=CheckChatSettings,
)


@plugin.command("ra", "检定", description="技能检定，如 .ra 60 侦查", max_times=MAX_REPETITIONS)
async def skill_check(context: CommandContext) -> None:
    """以 d100 对技能值进行检定。"""

    skill, reason = _split_arguments(context.args)
    rule = _current_rule(context)
    results = []

    for _ in range(context.times):
        roll = _RNG.randint(1, 100)
        level = check(rule, skill=skill, roll=roll)
        results.append(f"D100={roll}/{skill}，{level.name}")

    prefix = f"由于{reason}，" if reason else ""
    lead = f"{prefix}{context.display_name}进行了检定："

    if len(results) == 1:
        context.write(f"{lead}{results[0]}")
    else:
        context.write(lead)
        context.write("\n".join(results))


@plugin.command("rule", "检定规则", description="查看或设置检定规则")
async def show_or_set_rule(context: CommandContext) -> None:
    """查看当前检定规则，或切换到另一套规则。"""

    if not context.args:
        rule = _current_rule(context)

        context.write(f"当前检定规则：{rule.name}")
        context.write(rule.description)
        context.write("\n".join(f"{level.name}：{level.description}" for level in rule.levels))
        return

    target = get_rule(context.args)

    if target is None:
        available = "、".join(f"{item.id}（{item.name}）" for item in RULES.values())
        raise CommandError(f"没有这套检定规则……可选：{available}")

    settings = context.chat_settings(CheckChatSettings)
    settings.rule = target.id
    context.save_chat_settings(settings)
    context.write(f"检定规则已设置为：{target.name}")


def _current_rule(context: CommandContext) -> CheckRule:
    """取出会话当前使用的规则。

    Raises:
        CommandError: 会话中存的规则标识已不存在，通常是该规则在升级中被移除。
    """

    settings = context.chat_settings(CheckChatSettings)
    rule = get_rule(settings.rule)

    if rule is None:
        raise CommandError(f"本群设置的检定规则「{settings.rule}」不存在，请用 .rule 重新设置")

    return rule


def _split_arguments(args: str) -> tuple[int, str]:
    """把参数切分为技能值与检定理由。

    Raises:
        CommandError: 未给出技能值，或技能值超出合理范围。
    """

    match = _ARGUMENTS_PATTERN.fullmatch(args)

    if match is None or match.group("skill") is None:
        raise CommandError("请给出技能值，如 .ra 60 侦查")

    skill = int(match.group("skill"))

    if not 0 <= skill <= MAX_SKILL:
        raise CommandError(f"技能值需在 0 到 {MAX_SKILL} 之间……")

    return skill, match.group("reason")
