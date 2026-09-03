"""技能检定插件。

检定规则来自规则文件，由 :mod:`dicerobot.rules` 在启动时加载后注入，故插件由
:func:`build_plugin` 构造，而非在模块层声明。
"""

from __future__ import annotations

import random
import re
from collections.abc import Callable, Mapping

from pydantic import BaseModel, Field

from dicerobot.bot.context import CommandContext
from dicerobot.bot.plugin import Plugin
from dicerobot.enums import Scene
from dicerobot.errors import CommandError
from dicerobot.trpg.check import CheckRule, check
from dicerobot.trpg.dice import render_result
from dicerobot.trpg.percentile import BONUS_LABEL, PENALTY_LABEL, roll_percentile

__all__ = ["DEFAULT_RULE_ID", "CheckChatSettings", "build_plugin"]

MAX_REPETITIONS = 30
MAX_SKILL = 9999
MAX_MODIFIER_DICE = 10

# 默认检定规则
DEFAULT_RULE_ID = "coc7"

_RNG: random.Random = random.SystemRandom()

# 技能值写在最前，其余部分为检定理由。
_ARGUMENTS_PATTERN = re.compile(r"^(?P<skill>\d{1,4})?\s*(?P<reason>[\s\S]*)$")

# 奖惩骰的个数。`.rab2 60` 剥掉别名后是 `2 60`，与 `.rab 2 60` 同形，两者都表示两个
# 奖励骰。数字之后紧跟另一个数字时才是个数，`.rab 60 侦查` 的 60 仍是技能值。
_MODIFIER_COUNT_PATTERN = re.compile(r"^(?P<count>\d{1,2})\s+(?=\d)")

# 奖惩骰由指令别名指定，而非写在参数里：检定的参数是技能值而非掷骰表达式，修饰符写在
# 参数里会与检定理由的位置冲突。别名后缀 b/p 也是 OneDice 系骰子机器人的既有写法。
_BONUS_ALIASES = frozenset({"rab", "rahb", "rhab"})
_PENALTY_ALIASES = frozenset({"rap", "rahp", "rhap"})


class CheckChatSettings(BaseModel):
    """技能检定在某个会话中的设置。"""

    rule: str = Field(default=DEFAULT_RULE_ID)


def build_plugin(rules: Mapping[str, CheckRule]) -> Plugin:
    """构造插件。

    Args:
        rules: 可用的检定规则，键为小写的规则标识。
    """

    plugin = Plugin(
        name="check",
        display_name="技能检定",
        description="按检定规则以 d100 判定技能",
        version="1.0.0",
        chat_settings=CheckChatSettings,
    )

    @plugin.command(
        "ra",
        "rab",
        "rap",
        "检定",
        description="技能检定，如 .ra 60 侦查；.rab 带奖励骰，.rap 带惩罚骰",
        max_times=MAX_REPETITIONS,
    )
    async def skill_check(context: CommandContext) -> None:
        """以 d100 对技能值进行检定。"""

        _, lead, results = _check(context, _current_rule(rules, context))

        _write(context.write, lead, results)

    @plugin.command(
        "rah",
        "rha",
        "rahb",
        "rahp",
        "rhab",
        "rhap",
        "暗检定",
        description="暗检定，结果私聊发送，如 .rah 60 侦查；带 b 或 p 即奖惩暗检定",
        max_times=MAX_REPETITIONS,
    )
    async def hidden_check(context: CommandContext) -> None:
        """检定并把结果私聊发给发起者，群内只公布技能值与所用的奖惩骰。"""

        if context.message.scene is not Scene.GROUP:
            raise CommandError("暗检定只能在群聊中使用……")

        skill, lead, results = _check(context, _current_rule(rules, context))
        extra, penalty = _modifier(context.name)

        if extra:
            extra, _ = _split_modifier_count(context.args, extra)

        count = f"{extra} 个" if extra > 1 else ""
        label = f"，{count}{PENALTY_LABEL if penalty else BONUS_LABEL}" if extra else ""

        _write(context.write_private, lead, results)
        context.write(f"{context.display_name}进行了一次暗检定（{skill}{label}）")

    @plugin.command("rule", "检定规则", description="查看或设置检定规则")
    async def show_or_set_rule(context: CommandContext) -> None:
        """查看当前检定规则，或切换到另一套规则。"""

        if not context.args:
            rule = _current_rule(rules, context)

            context.write(f"当前检定规则：{rule.name}")
            context.write(rule.description)
            context.write("\n".join(f"{level.name}：{level.description}" for level in rule.levels))
            return

        target = rules.get(context.args.lower())

        if target is None:
            available = "、".join(f"{item.id}（{item.name}）" for item in rules.values())

            raise CommandError(f"没有这套检定规则……可选：{available}")

        settings = context.chat_settings(CheckChatSettings)
        settings.rule = target.id
        context.save_chat_settings(settings)
        context.write(f"检定规则已设置为：{target.name}")

    return plugin


def _modifier(name: str) -> tuple[int, bool]:
    """由指令别名取出奖惩骰。

    Returns:
        追加的十位骰个数与是否为惩罚骰。普通检定为 ``(0, False)``。
    """

    if name in _BONUS_ALIASES:
        return 1, False

    if name in _PENALTY_ALIASES:
        return 1, True

    return 0, False


def _split_modifier_count(args: str, default: int) -> tuple[int, str]:
    """取出写在技能值之前的奖惩骰个数。

    个数取 1 到 :data:`MAX_MODIFIER_DICE`，超出该范围的数字按技能值处理——``.rab 60 2``
    的 60 是技能值。

    Returns:
        奖惩骰个数与去掉个数之后的参数。
    """

    match = _MODIFIER_COUNT_PATTERN.match(args)

    if match is None:
        return default, args

    count = int(match.group("count"))

    if not 1 <= count <= MAX_MODIFIER_DICE:
        return default, args

    return count, args[match.end() :]


def _check(context: CommandContext, rule: CheckRule) -> tuple[int, str, list[str]]:
    """执行一次检定。

    Returns:
        技能值、开头一句与各次结果。公开检定与暗检定只在输出去向上不同，故共用此处。
    """

    extra, penalty = _modifier(context.name)
    args = context.args

    if extra:
        extra, args = _split_modifier_count(args, extra)

    skill, reason = _split_arguments(args)
    expression = f"D100{'P' if penalty else 'B'}{extra if extra > 1 else ''}" if extra else "D100"
    results = []

    for _ in range(context.times):
        roll = roll_percentile(rng=_RNG, extra=extra, penalty=penalty)
        level = check(rule, skill=skill, roll=roll.value)
        # 与掷骰共用一套呈现：无奖惩骰时折叠为 D100=57，带奖惩骰时先展开十位骰再给出结果。
        rendered = render_result(
            expression=expression,
            detailed=str(roll),
            brief=str(roll.value),
            value=roll.value,
        )
        results.append(f"{rendered}/{skill}，{level.name}")

    prefix = f"由于{reason}，" if reason else ""

    return skill, f"{prefix}{context.display_name}进行了检定：", results


def _write(write: Callable[[str], None], lead: str, results: list[str]) -> None:
    """把结果写入指定的输出通道：公开回复或私聊。"""

    if len(results) == 1:
        write(f"{lead}{results[0]}")
    else:
        write(lead)
        write("\n".join(results))


def _current_rule(rules: Mapping[str, CheckRule], context: CommandContext) -> CheckRule:
    """取出会话当前使用的规则。

    Raises:
        CommandError: 会话中记录的规则标识已不存在，通常是所有者改动或删除了该规则文件。
    """

    settings = context.chat_settings(CheckChatSettings)
    rule = rules.get(settings.rule.lower())

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
