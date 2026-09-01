"""系统插件。

指令的启停由三层开关决定：会话总开关、插件全局开关、插件在本会话的开关。本插件的
管理指令标记为常驻，否则一旦关闭便无从恢复。
"""

from __future__ import annotations

from dicerobot.bot.context import CommandContext, EventContext
from dicerobot.bot.plugin import Plugin
from dicerobot.bot.registry import Registry
from dicerobot.enums import MemberRole, Scene
from dicerobot.errors import CommandError
from dicerobot.qq.enums import EventType

__all__ = ["build_plugin"]

_ON = {"on", "开", "开启", "启用"}
_OFF = {"off", "关", "关闭", "停用"}

_MANAGERS = frozenset({MemberRole.OWNER, MemberRole.ADMIN})

_GREETING = "我是 DiceRobot，一个 TRPG 掷骰助手。发送 .help 查看可用指令。"


def build_plugin(registry: Registry) -> Plugin:
    """构造系统插件。

    需要注册表才能列出全部指令与插件，而注册表要等所有插件加载完毕才存在，因此本
    插件由加载器在最后构造，而非在模块层直接声明。
    """

    plugin = Plugin(
        name="system",
        display_name="系统",
        description="机器人与插件的管理指令",
        version="1.0.0",
    )

    @plugin.command("ping", description="确认机器人在线")
    async def ping(context: CommandContext) -> None:
        context.write("pong")

    @plugin.command("help", "帮助", description="列出可用指令")
    async def show_help(context: CommandContext) -> None:
        lines = ["可用指令："]

        for item in registry.plugins:
            for command in item.commands:
                aliases = " / ".join(f".{alias}" for alias in command.names)
                lines.append(f"{aliases}　{command.description}" if command.description else aliases)

        context.write("\n".join(lines))

    @plugin.command("bot", "机器人", description="启用或停用机器人，如 .bot off", requires_enabled=False)
    async def toggle_bot(context: CommandContext) -> None:
        argument = context.args.lower()

        if not argument:
            context.write("机器人当前已启用" if context.chat.enabled else "机器人当前已停用")
            return

        _require_manager(context, "启停机器人")

        if argument in _ON:
            context.chat.enabled = True
            context.write("已在本群启用机器人")
        elif argument in _OFF:
            context.chat.enabled = False
            context.write("已在本群停用机器人，用 .bot on 重新启用")
        else:
            raise CommandError("用法：.bot on 或 .bot off")

    @plugin.event(EventType.GROUP_ADD_ROBOT, EventType.FRIEND_ADD)
    async def greet(context: EventContext) -> None:
        """被加入群聊或添加为好友时自我介绍。

        事件自带 event_id，可作被动回复，因此不消耗主动消息配额。
        """

        context.write(_GREETING)

    @plugin.command("plugin", "插件", description="查看或启停插件，如 .plugin off dice", requires_enabled=False)
    async def manage_plugin(context: CommandContext) -> None:
        action, _, name = context.args.partition(" ")
        action = action.lower()
        name = name.strip().lower()

        if not action:
            await _list_plugins(context, registry)
            return

        if action not in _ON | _OFF:
            raise CommandError("用法：.plugin、.plugin on dice 或 .plugin off dice")

        _require_manager(context, "启停插件")

        target = registry.get(name)

        if target is None:
            raise CommandError(f"没有名为「{name}」的插件……")

        enabled = action in _ON

        if not enabled and target.always_available:
            raise CommandError(f"插件「{target.display_name}」含常驻指令，停用后将无法恢复，不允许关闭")

        state = await context.store.get_chat_plugin_state(context.message.scene, context.message.scene_id, target.name)
        state.enabled = enabled
        context.write(f"已在本群{'启用' if enabled else '停用'}插件：{target.display_name}")

    return plugin


def _require_manager(context: CommandContext, action: str) -> None:
    """群聊中的启停操作仅限群主与管理员。

    单聊里机器人只服务于对方本人，没有身份之分，不作限制；群内身份未知时一律拒绝。
    """

    if context.message.scene is not Scene.GROUP:
        return

    if context.message.role not in _MANAGERS:
        raise CommandError(f"只有群主和管理员可以{action}……")


async def _list_plugins(context: CommandContext, registry: Registry) -> None:
    lines = ["已加载的插件："]

    for item in registry.plugins:
        state = await context.store.get_chat_plugin_state(context.message.scene, context.message.scene_id, item.name)
        status = "已启用" if state.enabled else "已停用"
        lines.append(f"{item.name}　{item.display_name} v{item.version}　{status}")

    context.write("\n".join(lines))
