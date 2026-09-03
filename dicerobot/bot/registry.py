"""插件注册表与指令解析。

匹配采用最长前缀优先，因此 ``.ra 60`` 命中技能检定而非掷骰。相比按优先级数值排序，
无需为每个指令指定数字，也不会在新增指令时产生意外的匹配顺序。

注册在启动期完成，别名冲突立即抛出并指明涉及的两个插件——第三方插件接入后，别名
冲突会成为常见问题，错误信息必须能直接定位。
"""

from __future__ import annotations

import re
from dataclasses import dataclass

from dicerobot.bot.plugin import CommandSpec, EventSpec, Plugin
from dicerobot.qq.enums import EventType

__all__ = ["Invocation", "Registry"]

# 指令行：. 或 。前缀、指令体、可选的行尾 #N 重复次数。
# 次数须为一到三位的正整数：三位的上限避免 #999999 一类输入在解析阶段被视为有效值；
# #0 不构成次数，与 #侦查 一样留在指令体中，否则指令将执行零次却照常消耗一条回复配额。
_COMMAND_PATTERN = re.compile(r"^[.。]\s*(?P<body>.*?)\s*(?:#(?P<times>[1-9]\d{0,2}))?$", re.DOTALL)

# 次数的另一种写法：紧跟在指令之后，如 .r3#1d100 与 .r 3#1d100。OneDice 系的骰子机器人
# 用的是这一种，两种都收，玩家不必因换了机器人而改习惯。
_PREFIX_TIMES_PATTERN = re.compile(r"^(?P<times>[1-9]\d{0,2})#\s*")


@dataclass(frozen=True, slots=True)
class Invocation:
    """一次解析成功的指令调用。

    Attributes:
        plugin: 指令所属的插件。
        command: 命中的指令。
        name: 实际匹配到的别名。
        args: 别名之后的参数原文，已去除首尾空白。
        times: 重复次数，恒为正整数；未写 ``#N`` 时为 1。
    """

    plugin: Plugin
    command: CommandSpec
    name: str
    args: str
    times: int


class Registry:
    """已加载的插件及其指令。"""

    def __init__(self) -> None:
        self._plugins: dict[str, Plugin] = {}
        self._by_alias: dict[str, tuple[Plugin, CommandSpec]] = {}
        self._aliases_by_length: list[str] = []
        self._by_event: dict[EventType, list[tuple[Plugin, EventSpec]]] = {}

    @property
    def plugins(self) -> tuple[Plugin, ...]:
        """按注册顺序返回全部插件。"""

        return tuple(self._plugins.values())

    def get(self, name: str) -> Plugin | None:
        """按标识取插件，不存在时返回 ``None``。"""

        return self._plugins.get(name.lower())

    def add(self, plugin: Plugin) -> None:
        """注册一个插件及其全部指令。

        Raises:
            ValueError: 插件标识重复，或指令别名与已注册的插件冲突。
        """

        if plugin.name in self._plugins:
            raise ValueError(f"插件标识 {plugin.name!r} 已被注册")

        for command in plugin.commands:
            for alias in command.names:
                if (existing := self._by_alias.get(alias)) is not None:
                    raise ValueError(f"插件 {plugin.name!r} 与 {existing[0].name!r} 都注册了指令别名 {alias!r}")

        self._plugins[plugin.name] = plugin

        for command in plugin.commands:
            for alias in command.names:
                self._by_alias[alias] = (plugin, command)

        # 长别名在前，等长时按字典序，以保证匹配结果稳定。
        self._aliases_by_length = sorted(self._by_alias, key=lambda alias: (-len(alias), alias))

        for spec in plugin.events:
            for event_type in spec.event_types:
                self._by_event.setdefault(event_type, []).append((plugin, spec))

    def event_handlers(self, event_type: EventType) -> tuple[tuple[Plugin, EventSpec], ...]:
        """取出响应某类事件的全部处理器，按插件注册顺序排列。

        事件不像指令别名那样互斥，多个插件可以同时响应，因此这里不做冲突检查。
        """

        return tuple(self._by_event.get(event_type, ()))

    def resolve(self, text: str) -> Invocation | None:
        """把一行文本解析为指令调用。

        指令一律要求 ``.`` 或 ``。`` 前缀：群是否推送全量消息由群主或管理员随时设置，
        机器人无从得知当前模式，因而不能假定收到的每条消息都是发给自己的。

        重复次数写在指令之后（``.r 3#1d100``）或行尾（``.r 1d100#3``）皆可。两处同时写出
        无法判断以谁为准，与其猜一个，不如按未命中处理——本就无法解析的输入一律丢弃。

        Args:
            text: 消息正文，应已去除首尾空白。

        Returns:
            无法解析或未命中任何指令时返回 ``None``。
        """

        match = _COMMAND_PATTERN.fullmatch(text)

        if match is None:
            return None

        body = match.group("body")

        if not body:
            return None

        lowered = body.lower()

        for alias in self._aliases_by_length:
            if lowered.startswith(alias):
                plugin, command = self._by_alias[alias]
                args = body[len(alias) :].strip()
                suffix = match.group("times")
                prefix = _PREFIX_TIMES_PATTERN.match(args)

                if prefix is not None:
                    if suffix is not None:
                        return None

                    args = args[prefix.end() :]

                times = prefix.group("times") if prefix is not None else suffix

                return Invocation(
                    plugin=plugin,
                    command=command,
                    name=alias,
                    args=args,
                    times=int(times) if times else 1,
                )

        return None
