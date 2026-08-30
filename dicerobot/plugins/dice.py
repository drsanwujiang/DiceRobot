"""掷骰插件。"""

from __future__ import annotations

import random
import re

from pydantic import BaseModel, Field

from dicerobot.bot.context import CommandContext
from dicerobot.bot.plugin import Plugin
from dicerobot.errors import CommandError
from dicerobot.trpg.dice import DiceError, DiceSyntaxError, Limits, evaluate, parse

__all__ = ["DiceChatSettings", "plugin"]

MAX_REPETITIONS = 30
MIN_SURFACE = 1
MAX_SURFACE = 1000

# 使用系统熵源而非默认的梅森旋转，掷骰结果直接影响游戏进程，不应存在可预测的序列。
_RNG: random.Random = random.SystemRandom()

# 掷骰表达式允许出现的字符。据此切出开头的表达式，其余部分视为掷骰理由。
_ARGUMENTS_PATTERN = re.compile(r"^(?P<expression>[0-9dDkKlL+\-*/xX×÷()（）]*)\s*(?P<reason>[\s\S]*)$")

# 用于判断切出的"表达式"是否为误匹配，如 `.r kick` 的开头 k 实为理由的一部分。
_LOOKS_LIKE_EXPRESSION = re.compile(r"[0-9dD]")


class DiceChatSettings(BaseModel):
    """掷骰在某个会话中的设置。"""

    default_surface: int = Field(default=100, ge=MIN_SURFACE, le=MAX_SURFACE)


plugin = Plugin(
    name="dice",
    display_name="掷骰",
    description="掷一个或一组骰子",
    version="1.0.0",
    chat_settings=DiceChatSettings,
)


@plugin.command("r", "roll", "掷骰", description="掷骰，如 .r 3d6+2 侦查", max_times=MAX_REPETITIONS)
async def roll(context: CommandContext) -> None:
    """掷一个或一组骰子。"""

    expression, reason = _split_arguments(context.args)

    if context.times > MAX_REPETITIONS:
        raise CommandError(f"一次最多重复 {MAX_REPETITIONS} 次……")

    settings = context.chat_settings(DiceChatSettings)
    limits = Limits(default_surface=settings.default_surface)

    try:
        node = parse(expression)
        results = [str(evaluate(node, rng=_RNG, limits=limits)) for _ in range(context.times)]
    except DiceSyntaxError as e:
        raise CommandError(f"掷骰表达式第 {e.position + 1} 个字符处有问题：{e.message}") from e
    except DiceError as e:
        raise CommandError(str(e)) from e

    prefix = f"由于{reason}，" if reason else ""
    lead = f"{prefix}{context.display_name}骰出了："

    if len(results) == 1:
        context.write(f"{lead}{results[0]}")
    else:
        # 缓冲区以换行拼接各段，故此处不应自带换行。全部内容合并为一条消息发出。
        context.write(lead)
        context.write("\n".join(results))


@plugin.command("set", "默认骰", description="查看或设置默认骰，如 .set 20")
async def default_surface(context: CommandContext) -> None:
    """查看或设置未写明面数时使用的骰子面数。"""

    settings = context.chat_settings(DiceChatSettings)

    if not context.args:
        context.write(f"当前默认骰：D{settings.default_surface}")
        return

    if not context.args.isdecimal():
        raise CommandError("用法：.set 100")

    surface = int(context.args)

    if not MIN_SURFACE <= surface <= MAX_SURFACE:
        raise CommandError(f"默认骰面数需在 {MIN_SURFACE} 到 {MAX_SURFACE} 之间……")

    settings.default_surface = surface
    context.save_chat_settings(settings)
    context.write(f"默认骰已设置为：D{surface}")


def _split_arguments(args: str) -> tuple[str, str]:
    """把参数切分为表达式与掷骰理由。

    未写表达式时默认掷一个骰子，使 ``.r`` 与 ``.r 侦查`` 均可直接使用。

    理由若以表达式允许的字符开头，切出的"表达式"将既无数字也无 d，此时整段按理由
    处理，避免抛出令人费解的语法错误。
    """

    match = _ARGUMENTS_PATTERN.fullmatch(args)

    if match is None:  # pragma: no cover - 模式可匹配任意字符串
        return "d", args

    expression = match.group("expression")
    reason = match.group("reason")

    if not expression:
        return "d", reason

    if not _LOOKS_LIKE_EXPRESSION.search(expression):
        return "d", args

    return expression, reason
