"""插件加载。

内置插件由显式清单声明，第三方插件通过 entry points 发现。两者都在启动期加载，任何
一个失败都直接抛出——静默跳过一个插件，故障将表现为某条指令无响应，难以排查。

第三方插件在自己的项目中声明::

    [project.entry-points."dicerobot.plugins"]
    mycards = "mycards:plugin"
"""

from __future__ import annotations

import importlib
from collections.abc import Mapping
from importlib.metadata import entry_points

from loguru import logger

from dicerobot.bot.plugin import Plugin
from dicerobot.bot.registry import Registry
from dicerobot.errors import ConfigurationError
from dicerobot.plugins.check import build_plugin as build_check_plugin
from dicerobot.plugins.system import build_plugin as build_system_plugin
from dicerobot.trpg.check import CheckRule

__all__ = ["ENTRY_POINT_GROUP", "load_registry"]

ENTRY_POINT_GROUP = "dicerobot.plugins"

# 检定与系统插件需要启动期才有的依赖，故不在此列，由下方显式构造。
_BUILTIN_MODULES = (
    "dicerobot.plugins.dice",
    "dicerobot.plugins.nickname",
)

_PLUGIN_ATTRIBUTE = "plugin"


def load_registry(rules: Mapping[str, CheckRule], *, include_entry_points: bool = True) -> Registry:
    """加载全部插件并装配注册表。

    Args:
        rules: 检定规则，由 :func:`dicerobot.rules.load_rules` 在启动时读入。
        include_entry_points: 是否加载第三方插件。测试中通常关闭，以免受环境中已安装
            的插件影响。

    Raises:
        ConfigurationError: 插件无法导入，或导出的对象不是插件。
        ValueError: 插件标识或指令别名冲突。
    """

    registry = Registry()

    for module_name in _BUILTIN_MODULES:
        registry.add(_load_module(module_name))

    registry.add(build_check_plugin(rules))

    if include_entry_points:
        for entry_point in entry_points(group=ENTRY_POINT_GROUP):
            registry.add(_load_entry_point(entry_point.name, entry_point.load))

    # 系统插件的 .help 与 .plugin 需要遍历注册表，因此在其余插件之后构造。
    # 它捕获的是注册表对象本身，加载完成后即可看到全部插件。
    registry.add(build_system_plugin(registry))

    for plugin in registry.plugins:
        aliases = [alias for command in plugin.commands for alias in command.names]
        logger.debug("插件 {} v{} 的指令别名：{}", plugin.name, plugin.version, "、".join(aliases) or "无")

    logger.info(
        "已加载 {} 个插件，共 {} 条指令",
        len(registry.plugins),
        sum(len(plugin.commands) for plugin in registry.plugins),
    )

    return registry


def _load_module(module_name: str) -> Plugin:
    try:
        module = importlib.import_module(module_name)
    except ImportError as e:
        raise ConfigurationError(f"插件模块 {module_name} 导入失败：{e}") from e

    plugin = getattr(module, _PLUGIN_ATTRIBUTE, None)

    if not isinstance(plugin, Plugin):
        raise ConfigurationError(f"插件模块 {module_name} 未导出名为 {_PLUGIN_ATTRIBUTE} 的插件对象")

    return plugin


def _load_entry_point(name: str, load: object) -> Plugin:
    if not callable(load):  # pragma: no cover - entry point 的 load 始终可调用
        raise ConfigurationError(f"插件入口 {name} 无法加载")

    try:
        plugin = load()
    except Exception as e:
        raise ConfigurationError(f"插件入口 {name} 加载失败：{e}") from e

    if not isinstance(plugin, Plugin):
        raise ConfigurationError(f"插件入口 {name} 指向的不是插件对象")

    logger.info("已加载第三方插件 {}（{}）", plugin.name, plugin.version)

    return plugin
