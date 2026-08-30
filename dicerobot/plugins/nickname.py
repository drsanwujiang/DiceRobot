"""昵称插件。

平台不提供用户昵称，只给出不透明的 openid，因此昵称必须由用户自行设置。这是掷骰
结果中唯一的人类可读标识来源。
"""

from __future__ import annotations

from dicerobot.bot.context import CommandContext
from dicerobot.bot.plugin import Plugin
from dicerobot.errors import CommandError

__all__ = ["plugin"]

MAX_NICKNAME_LENGTH = 32

_CLEAR = {"clear", "清除"}

plugin = Plugin(
    name="nickname",
    display_name="昵称",
    description="设置在本会话中显示的名字",
    version="1.0.0",
)


@plugin.command("nn", "昵称", description="设置昵称，.nn 清除 可恢复默认")
async def nickname(context: CommandContext) -> None:
    """设置、查看或清除本会话中的昵称。"""

    name = context.args

    if not name:
        context.write(f"当前昵称：{context.display_name}")
        return

    if name in _CLEAR:
        context.member.nickname = None
        context.write(f"已清除昵称，现在称呼你为{context.display_name}")
        return

    if len(name) > MAX_NICKNAME_LENGTH:
        raise CommandError(f"昵称最长 {MAX_NICKNAME_LENGTH} 个字符……")

    context.member.nickname = name
    context.write(f"已将昵称设置为：{name}")
