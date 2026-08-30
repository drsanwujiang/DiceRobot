"""系统插件的测试。"""

from __future__ import annotations

from typing import Any

import pytest

from dicerobot.bot.loader import load_registry
from dicerobot.bot.plugin import CommandHandler, Plugin
from dicerobot.errors import CommandError
from tests.conftest import CommandRunner


@pytest.fixture
def registry() -> Any:
    return load_registry(include_entry_points=False)


@pytest.fixture
def system(registry: Any) -> Plugin:
    plugin = registry.get("system")
    assert plugin is not None

    return plugin


@pytest.fixture
async def runner(make_runner: Any, system: Plugin) -> CommandRunner:
    return await make_runner(system)


def handler(plugin: Plugin, alias: str) -> CommandHandler:
    """按别名取出插件的处理函数。"""

    for command in plugin.commands:
        if alias in command.names:
            return command.handler

    raise AssertionError(f"插件 {plugin.name} 没有别名 {alias}")


class TestPing:
    async def test_replies_pong(self, runner: CommandRunner, system: Plugin) -> None:
        assert await runner.run(handler(system, "ping")) == "pong"


class TestHelp:
    async def test_lists_commands_from_every_plugin(self, runner: CommandRunner, system: Plugin) -> None:
        content = await runner.run(handler(system, "help"))

        assert ".r" in content
        assert ".ra" in content
        assert ".nn" in content
        assert ".plugin" in content


class TestToggleBot:
    async def test_turns_the_bot_off_and_on(self, runner: CommandRunner, system: Plugin) -> None:
        toggle = handler(system, "bot")

        await runner.run(toggle, "off")
        assert runner.chat.enabled is False

        await runner.run(toggle, "on")
        assert runner.chat.enabled is True

    async def test_reports_the_current_state(self, runner: CommandRunner, system: Plugin) -> None:
        assert "已启用" in await runner.run(handler(system, "bot"))

    async def test_rejects_other_arguments(self, runner: CommandRunner, system: Plugin) -> None:
        with pytest.raises(CommandError, match="用法"):
            await runner.run(handler(system, "bot"), "maybe")


class TestManagePlugin:
    async def test_lists_plugins_with_their_state(self, runner: CommandRunner, system: Plugin) -> None:
        content = await runner.run(handler(system, "plugin"))

        assert "dice" in content
        assert "已启用" in content

    async def test_disables_and_reenables_a_plugin(self, runner: CommandRunner, system: Plugin) -> None:
        manage = handler(system, "plugin")

        await runner.run(manage, "off dice")
        state = await runner.store.get_chat_plugin_state(runner.chat.scene, runner.chat.openid, "dice")
        assert state.enabled is False

        await runner.run(manage, "on dice")
        assert state.enabled is True

    async def test_refuses_to_disable_a_plugin_with_always_available_commands(
        self, runner: CommandRunner, system: Plugin
    ) -> None:
        """停用系统插件之后将失去重新启用的手段。"""

        with pytest.raises(CommandError, match="无法恢复"):
            await runner.run(handler(system, "plugin"), "off system")

    async def test_rejects_an_unknown_plugin(self, runner: CommandRunner, system: Plugin) -> None:
        with pytest.raises(CommandError, match="没有名为"):
            await runner.run(handler(system, "plugin"), "off nonexistent")

    async def test_rejects_an_unknown_action(self, runner: CommandRunner, system: Plugin) -> None:
        with pytest.raises(CommandError, match="用法"):
            await runner.run(handler(system, "plugin"), "delete dice")
