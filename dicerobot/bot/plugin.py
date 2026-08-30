"""插件定义。

一个插件是一组相关功能的封装：若干指令入口、若干事件处理器、可选的设置结构，以及
独立的启停状态。插件对象在模块层声明，处理函数以装饰器挂载。

装饰器原样返回处理函数，因此处理函数仍是普通的异步函数，可以脱离插件与运行时直接
调用，测试无需构造插件实例。
"""

from __future__ import annotations

import re
from collections.abc import Awaitable, Callable
from dataclasses import dataclass, field

from pydantic import BaseModel

from dicerobot.bot.context import CommandContext, EventContext
from dicerobot.qq.enums import EventType

__all__ = ["CommandHandler", "CommandSpec", "EventHandler", "EventSpec", "Plugin"]

type CommandHandler = Callable[[CommandContext], Awaitable[None]]
type EventHandler = Callable[[EventContext], Awaitable[None]]

# 插件名会作为数据库主键与指令参数使用，限定为小写标识符。
_NAME_PATTERN = re.compile(r"^[a-z][a-z0-9_]*$")


@dataclass(frozen=True, slots=True)
class CommandSpec:
    """一个指令入口。

    Attributes:
        names: 全部别名，均以小写存储。
        handler: 处理函数。
        description: 用于 ``.help`` 的简介。
        max_times: 允许的最大重复次数。
        requires_enabled: 是否要求机器人与所属插件均处于启用状态。
            开关类指令须设为假，否则关闭之后无法再打开。
    """

    names: tuple[str, ...]
    handler: CommandHandler = field(compare=False)
    description: str = ""
    max_times: int = 1
    requires_enabled: bool = True


@dataclass(frozen=True, slots=True)
class EventSpec:
    """一个事件处理器。

    Attributes:
        event_types: 该处理器响应的事件类型。
        handler: 处理函数。
    """

    event_types: tuple[EventType, ...]
    handler: EventHandler = field(compare=False)


class Plugin:
    """一个插件。"""

    def __init__(
        self,
        *,
        name: str,
        display_name: str,
        description: str = "",
        version: str = "0.1.0",
        settings: type[BaseModel] | None = None,
        chat_settings: type[BaseModel] | None = None,
    ) -> None:
        """
        Args:
            name: 插件标识，同时作为设置的存储键与 ``.plugin`` 的参数。
            display_name: 展示名称。
            description: 简介。
            version: 版本号。
            settings: 全局设置的结构。省略表示该插件没有全局设置。
            chat_settings: 会话级设置的结构。

        Raises:
            ValueError: 插件标识不是合法的小写标识符。
        """

        if not _NAME_PATTERN.fullmatch(name):
            raise ValueError(f"插件标识 {name!r} 须为小写字母、数字与下划线，且以字母开头")

        self.name = name
        self.display_name = display_name
        self.description = description
        self.version = version
        self.settings = settings
        self.chat_settings = chat_settings

        self._commands: list[CommandSpec] = []
        self._events: list[EventSpec] = []

    @property
    def commands(self) -> tuple[CommandSpec, ...]:
        """按声明顺序返回全部指令入口。"""

        return tuple(self._commands)

    @property
    def events(self) -> tuple[EventSpec, ...]:
        """按声明顺序返回全部事件处理器。"""

        return tuple(self._events)

    @property
    def always_available(self) -> bool:
        """插件是否含有常驻指令。

        含常驻指令的插件不可停用，否则将失去重新启用的手段。
        """

        return any(not command.requires_enabled for command in self._commands)

    def command(
        self,
        *names: str,
        description: str = "",
        max_times: int = 1,
        requires_enabled: bool = True,
    ) -> Callable[[CommandHandler], CommandHandler]:
        """把一个处理函数注册为指令入口。

        同一个处理函数可以挂多个别名；同一个插件也可以有多个入口。

        Raises:
            ValueError: 未提供别名，或别名为空。
        """

        if not names:
            raise ValueError("指令至少需要一个别名")

        lowered = tuple(alias.lower() for alias in names)

        if not all(lowered):
            raise ValueError("指令别名不能为空")

        def decorator(handler: CommandHandler) -> CommandHandler:
            self._commands.append(
                CommandSpec(
                    names=lowered,
                    handler=handler,
                    description=description,
                    max_times=max_times,
                    requires_enabled=requires_enabled,
                )
            )

            return handler

        return decorator

    def event(self, *event_types: EventType) -> Callable[[EventHandler], EventHandler]:
        """把一个处理函数注册为事件处理器。

        同一事件可以被多个插件响应，它们的输出会合并为一条回复。

        Raises:
            ValueError: 未提供事件类型。
        """

        if not event_types:
            raise ValueError("事件处理器至少需要一个事件类型")

        def decorator(handler: EventHandler) -> EventHandler:
            self._events.append(EventSpec(event_types=event_types, handler=handler))

            return handler

        return decorator

    def __repr__(self) -> str:
        return f"Plugin(name={self.name!r}, version={self.version!r})"
