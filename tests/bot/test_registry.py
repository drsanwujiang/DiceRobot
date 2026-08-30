"""插件注册与指令解析的测试。"""

from __future__ import annotations

import pytest

from dicerobot.bot.context import CommandContext, EventContext
from dicerobot.bot.plugin import Plugin
from dicerobot.bot.registry import Registry
from dicerobot.qq.enums import EventType


async def noop(context: CommandContext) -> None:
    pass


async def on_event(context: EventContext) -> None:
    pass


def make_plugin(name: str, *aliases: tuple[str, ...]) -> Plugin:
    plugin = Plugin(name=name, display_name=name)

    for group in aliases:
        plugin.command(*group)(noop)

    return plugin


@pytest.fixture
def registry() -> Registry:
    instance = Registry()
    instance.add(make_plugin("dice", ("r", "掷骰"), ("set",)))
    instance.add(make_plugin("check", ("ra", "检定"), ("rule",)))
    instance.add(make_plugin("system", ("help", "帮助")))

    return instance


class TestPluginDeclaration:
    def test_one_plugin_can_own_several_entries(self) -> None:
        plugin = make_plugin("dice", ("r",), ("set",))

        assert len(plugin.commands) == 2

    def test_decorator_returns_the_handler_unchanged(self) -> None:
        """处理函数须保持可直接调用，测试才不必构造插件。"""

        plugin = Plugin(name="dice", display_name="掷骰")

        assert plugin.command("r")(noop) is noop

    def test_rejects_an_invalid_name(self) -> None:
        with pytest.raises(ValueError, match="小写字母"):
            Plugin(name="Dice-1", display_name="掷骰")

    def test_rejects_a_command_without_aliases(self) -> None:
        with pytest.raises(ValueError, match="至少需要一个别名"):
            Plugin(name="dice", display_name="掷骰").command()

    def test_reports_whether_it_has_an_always_available_command(self) -> None:
        plugin = Plugin(name="system", display_name="系统")
        plugin.command("bot", requires_enabled=False)(noop)

        assert plugin.always_available is True
        assert make_plugin("dice", ("r",)).always_available is False


class TestLongestPrefixMatch:
    def test_longer_alias_wins_over_shorter(self, registry: Registry) -> None:
        """按优先级排序原本即为模拟此行为，现由匹配规则本身保证。"""

        invocation = registry.resolve(".ra 60")

        assert invocation is not None
        assert invocation.plugin.name == "check"
        assert invocation.name == "ra"
        assert invocation.args == "60"

    def test_shorter_alias_still_matches_when_longer_does_not(self, registry: Registry) -> None:
        invocation = registry.resolve(".r 1d100")

        assert invocation is not None
        assert invocation.plugin.name == "dice"
        assert invocation.args == "1d100"

    def test_matches_without_separator(self, registry: Registry) -> None:
        """`.r1d100` 与 `.r 1d100` 等价。"""

        invocation = registry.resolve(".r1d100")

        assert invocation is not None
        assert invocation.name == "r"
        assert invocation.args == "1d100"

    def test_full_word_alias_is_not_shadowed(self, registry: Registry) -> None:
        invocation = registry.resolve(".rule")

        assert invocation is not None
        assert invocation.name == "rule"

    def test_is_case_insensitive(self, registry: Registry) -> None:
        invocation = registry.resolve(".RA 60")

        assert invocation is not None
        assert invocation.name == "ra"

    def test_matches_chinese_alias(self, registry: Registry) -> None:
        invocation = registry.resolve(".检定 60")

        assert invocation is not None
        assert invocation.plugin.name == "check"

    def test_unknown_command_resolves_to_nothing(self, registry: Registry) -> None:
        assert registry.resolve(".zzz") is None


class TestPrefix:
    @pytest.mark.parametrize("text", [".r 1d100", "。r 1d100", ". r 1d100"])
    def test_accepts_both_half_and_full_width_prefix(self, registry: Registry, text: str) -> None:
        assert registry.resolve(text) is not None

    def test_rejects_missing_prefix(self, registry: Registry) -> None:
        """群随时可能被开启全量消息推送，无前缀的闲聊不得被识别为指令。"""

        assert registry.resolve("r 1d100") is None

    def test_bare_prefix_is_not_a_command(self, registry: Registry) -> None:
        assert registry.resolve(".") is None


class TestRepetition:
    def test_parses_trailing_repetition(self, registry: Registry) -> None:
        invocation = registry.resolve(".r 1d100#5")

        assert invocation is not None
        assert invocation.args == "1d100"
        assert invocation.times == 5

    def test_defaults_to_one(self, registry: Registry) -> None:
        invocation = registry.resolve(".r 1d100")

        assert invocation is not None
        assert invocation.times == 1

    def test_non_numeric_hash_is_part_of_the_arguments(self, registry: Registry) -> None:
        """`#侦查` 属于掷骰理由，而非重复次数。"""

        invocation = registry.resolve(".r 1d100 #侦查")

        assert invocation is not None
        assert invocation.args == "1d100 #侦查"
        assert invocation.times == 1


class TestConflicts:
    def test_rejects_a_duplicate_plugin_name(self, registry: Registry) -> None:
        with pytest.raises(ValueError, match="已被注册"):
            registry.add(make_plugin("dice", ("x",)))

    def test_alias_conflict_names_both_plugins(self, registry: Registry) -> None:
        """第三方插件接入后别名撞车会变常见，错误信息必须能直接定位。"""

        with pytest.raises(ValueError, match=r"'mine'.*'dice'.*'r'"):
            registry.add(make_plugin("mine", ("r",)))

    def test_a_rejected_plugin_leaves_no_trace(self, registry: Registry) -> None:
        with pytest.raises(ValueError, match="都注册了指令别名"):
            registry.add(make_plugin("mine", ("nn",), ("r",)))

        # 冲突在写入注册表之前就被发现，插件的其他别名不应残留。
        assert registry.get("mine") is None
        assert registry.resolve(".nn") is None


class TestEventHandlers:
    def test_registers_a_handler_for_several_event_types(self) -> None:
        plugin = Plugin(name="greeter", display_name="欢迎")
        plugin.event(EventType.GROUP_ADD_ROBOT, EventType.FRIEND_ADD)(on_event)

        registry = Registry()
        registry.add(plugin)

        assert len(registry.event_handlers(EventType.GROUP_ADD_ROBOT)) == 1
        assert len(registry.event_handlers(EventType.FRIEND_ADD)) == 1

    def test_several_plugins_may_handle_the_same_event(self) -> None:
        """事件不像指令别名那样互斥，不应产生冲突。"""

        registry = Registry()

        for name in ("first", "second"):
            plugin = Plugin(name=name, display_name=name)
            plugin.event(EventType.GROUP_ADD_ROBOT)(on_event)
            registry.add(plugin)

        handlers = registry.event_handlers(EventType.GROUP_ADD_ROBOT)

        assert [plugin.name for plugin, _ in handlers] == ["first", "second"]

    def test_unhandled_event_returns_nothing(self) -> None:
        assert Registry().event_handlers(EventType.GROUP_DEL_ROBOT) == ()

    def test_rejects_a_handler_without_event_types(self) -> None:
        with pytest.raises(ValueError, match="至少需要一个事件类型"):
            Plugin(name="greeter", display_name="欢迎").event()

    def test_decorator_returns_the_handler_unchanged(self) -> None:
        plugin = Plugin(name="greeter", display_name="欢迎")

        assert plugin.event(EventType.GROUP_ADD_ROBOT)(on_event) is on_event
