"""掷骰插件。"""

from __future__ import annotations

import random
import re
from collections.abc import Callable

from pydantic import BaseModel, Field

from dicerobot.bot.context import CommandContext
from dicerobot.bot.plugin import Plugin
from dicerobot.enums import Scene
from dicerobot.errors import CommandError
from dicerobot.trpg.dice import DiceError, DiceSyntaxError, Limits, evaluate, parse

__all__ = ["DiceChatSettings", "plugin"]

MAX_REPETITIONS = 30
MIN_SURFACE = 1
MAX_SURFACE = 1000

# 使用系统熵源而非默认的梅森旋转，掷骰结果直接影响游戏进程，不应存在可预测的序列。
_RNG: random.Random = random.SystemRandom()

# 掷骰表达式允许出现的字符。据此切出开头的表达式，其余部分视为掷骰理由。
_ARGUMENTS_PATTERN = re.compile(r"^(?P<expression>[0-9dDkKqQbBpP^+\-*/xX×÷()（）]*)(?P<gap>\s*)(?P<reason>[\s\S]*)$")

# 用于判断切出的"表达式"是否为误匹配，如 `.r kick` 切出的 k。
_LOOKS_LIKE_EXPRESSION = re.compile(r"[0-9dDbBpP]")

# 表达式与理由紧邻时，用于判断是否切在词中间。
_WORD_CHARACTER = re.compile(r"[0-9A-Za-z]")


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

    _, lead, results = _roll(context)

    _write(context.write, lead, results)


@plugin.command("rh", "暗骰", description="暗骰，结果私聊发送，如 .rh 1d100 侦查", max_times=MAX_REPETITIONS)
async def hidden_roll(context: CommandContext) -> None:
    """掷骰并把结果私聊发给掷骰者，群内只公布掷骰表达式。"""

    if context.message.scene is not Scene.GROUP:
        raise CommandError("暗骰只能在群聊中使用……")

    expression, lead, results = _roll(context)

    _write(context.write_private, lead, results)
    # 公开掷骰表达式、隐藏结果，是 TRPG 的惯例。
    context.write(f"{context.display_name}进行了一次暗骰（{expression}）")


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


def _roll(context: CommandContext) -> tuple[str, str, list[str]]:
    """执行一次掷骰。

    Returns:
        表达式、开头一句与各次结果。公开掷骰与暗骰只在输出去向上不同，故共用此处。

    Raises:
        CommandError: 重复次数超限，或表达式无法解析、无法求值。
    """

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

    return expression, f"{prefix}{context.display_name}骰出了：", results


def _write(write: Callable[[str], None], lead: str, results: list[str]) -> None:
    """把结果写入指定的输出通道：公开回复或私聊。"""

    if len(results) == 1:
        write(f"{lead}{results[0]}")
    else:
        # 缓冲区以换行拼接各段，故此处不应自带换行。全部内容合并为一条消息发出。
        write(lead)
        write("\n".join(results))


def _split_arguments(args: str) -> tuple[str, str]:
    """把参数切分为表达式与掷骰理由。

    未写表达式时默认掷一个骰子，使 ``.r`` 与 ``.r 侦查`` 均可直接使用。

    理由若以表达式允许的字符开头，会被切出一段并非表达式的内容，两条规则据此排除：切出
    的部分既无数字也无骰子算符时，整段按理由处理；两者之间没有空白且理由以 ASCII 字母或
    数字开头时同样如此——``.r bomb`` 与 ``.r dodge`` 都会被切在词中间。中文理由不以 ASCII
    字符开头，``.r 1d100侦查`` 仍照常解析。
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

    if not match.group("gap") and _WORD_CHARACTER.match(reason):
        return "d", args

    return expression, reason
