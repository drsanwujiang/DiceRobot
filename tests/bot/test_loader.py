"""插件加载的测试。"""

from __future__ import annotations

from collections.abc import Mapping

import pytest

from dicerobot.bot.loader import load_registry
from dicerobot.bot.plugin import Plugin
from dicerobot.errors import ConfigurationError
from dicerobot.trpg.check import CheckRule


@pytest.fixture
def registry(rules: Mapping[str, CheckRule]) -> object:
    # 关闭 entry points，以免受环境中已安装的第三方插件影响。
    return load_registry(rules, include_entry_points=False)


class TestBuiltinPlugins:
    def test_loads_every_builtin(self, rules: Mapping[str, CheckRule]) -> None:
        names = {plugin.name for plugin in load_registry(rules, include_entry_points=False).plugins}

        assert names == {"dice", "check", "nickname", "system"}

    @pytest.mark.parametrize("alias", ["r", "set", "ra", "rule", "nn", "ping", "help", "bot", "plugin"])
    def test_every_expected_alias_resolves(self, alias: str, rules: Mapping[str, CheckRule]) -> None:
        assert load_registry(rules, include_entry_points=False).resolve(f".{alias}") is not None

    def test_system_plugin_sees_plugins_loaded_before_it(self, rules: Mapping[str, CheckRule]) -> None:
        """系统插件最后构造，但捕获的是注册表对象，因此能看到全部插件。"""

        loaded = load_registry(rules, include_entry_points=False)

        assert loaded.get("system") is not None
        assert len(loaded.plugins) == 4

    def test_system_plugin_cannot_be_disabled(self, rules: Mapping[str, CheckRule]) -> None:
        system = load_registry(rules, include_entry_points=False).get("system")

        assert system is not None
        assert system.always_available is True


class TestFailures:
    def test_missing_module_is_reported(self, monkeypatch: pytest.MonkeyPatch, rules: Mapping[str, CheckRule]) -> None:
        """加载失败必须直接抛出，静默跳过只会表现为某条指令不响应。"""

        monkeypatch.setattr("dicerobot.bot.loader._BUILTIN_MODULES", ("dicerobot.plugins.nonexistent",))

        with pytest.raises(ConfigurationError, match="导入失败"):
            load_registry(rules, include_entry_points=False)

    def test_module_without_a_plugin_object_is_reported(
        self, monkeypatch: pytest.MonkeyPatch, rules: Mapping[str, CheckRule]
    ) -> None:
        monkeypatch.setattr("dicerobot.bot.loader._BUILTIN_MODULES", ("dicerobot.errors",))

        with pytest.raises(ConfigurationError, match="未导出"):
            load_registry(rules, include_entry_points=False)


class TestEntryPoints:
    def test_third_party_plugin_is_registered(
        self, monkeypatch: pytest.MonkeyPatch, rules: Mapping[str, CheckRule]
    ) -> None:
        third_party = Plugin(name="cards", display_name="人物卡", version="2.0.0")

        class FakeEntryPoint:
            name = "cards"

            @staticmethod
            def load() -> Plugin:
                return third_party

        monkeypatch.setattr("dicerobot.bot.loader.entry_points", lambda group: [FakeEntryPoint()])
        loaded = load_registry(rules, include_entry_points=True)

        assert loaded.get("cards") is third_party

    def test_a_failing_entry_point_is_reported(
        self, monkeypatch: pytest.MonkeyPatch, rules: Mapping[str, CheckRule]
    ) -> None:
        class BrokenEntryPoint:
            name = "broken"

            @staticmethod
            def load() -> Plugin:
                raise RuntimeError("boom")

        monkeypatch.setattr("dicerobot.bot.loader.entry_points", lambda group: [BrokenEntryPoint()])

        with pytest.raises(ConfigurationError, match="加载失败"):
            load_registry(rules, include_entry_points=True)

    def test_a_non_plugin_entry_point_is_reported(
        self, monkeypatch: pytest.MonkeyPatch, rules: Mapping[str, CheckRule]
    ) -> None:
        class WrongEntryPoint:
            name = "wrong"

            @staticmethod
            def load() -> str:
                return "not a plugin"

        monkeypatch.setattr("dicerobot.bot.loader.entry_points", lambda group: [WrongEntryPoint()])

        with pytest.raises(ConfigurationError, match="不是插件对象"):
            load_registry(rules, include_entry_points=True)
