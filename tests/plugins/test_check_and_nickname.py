"""检定与昵称插件的测试。"""

from __future__ import annotations

import re
from collections.abc import Mapping
from typing import Any

import pytest

from dicerobot.bot.plugin import CommandHandler, Plugin
from dicerobot.enums import Scene
from dicerobot.errors import CommandError
from dicerobot.plugins.check import build_plugin as build_check_plugin
from dicerobot.plugins.nickname import MAX_NICKNAME_LENGTH, nickname
from dicerobot.plugins.nickname import plugin as nickname_plugin
from dicerobot.trpg.check import CheckRule
from tests.conftest import CommandRunner


@pytest.fixture
def check_plugin(rules: Mapping[str, CheckRule]) -> Plugin:
    return build_check_plugin(rules)


@pytest.fixture
async def checker(make_runner: Any, check_plugin: Plugin) -> CommandRunner:
    return await make_runner(check_plugin)


def handler(plugin: Plugin, alias: str) -> CommandHandler:
    """按别名取出插件的处理函数。插件由 build_plugin 构造，取不到模块级的函数。"""

    for command in plugin.commands:
        if alias in command.names:
            return command.handler

    raise AssertionError(f"插件 {plugin.name} 没有别名 {alias}")


@pytest.fixture
async def namer(make_runner: Any) -> CommandRunner:
    return await make_runner(nickname_plugin)


class TestSkillCheck:
    async def test_reports_the_roll_and_the_level(self, checker: CommandRunner, check_plugin: Plugin) -> None:
        checker.member.nickname = "调查员"
        content = await checker.run(handler(check_plugin, "ra"), "60")

        assert content.startswith("调查员进行了检定：D100=")
        assert "/60，" in content

    async def test_includes_the_reason(self, checker: CommandRunner, check_plugin: Plugin) -> None:
        assert (await checker.run(handler(check_plugin, "ra"), "60 侦查")).startswith("由于侦查，")

    async def test_each_repetition_occupies_its_own_line(self, checker: CommandRunner, check_plugin: Plugin) -> None:
        content = await checker.run(handler(check_plugin, "ra"), "60", times=3)

        # 首行为称呼，其后每次检定各占一行。
        assert len(content.splitlines()) == 4

    async def test_requires_a_skill_value(self, checker: CommandRunner, check_plugin: Plugin) -> None:
        with pytest.raises(CommandError, match="请给出技能值"):
            await checker.run(handler(check_plugin, "ra"), "侦查")


class TestModifierCheck:
    """奖惩检定。取舍本身由 tests/trpg 覆盖，此处只验证别名到修饰骰的映射与呈现。"""

    async def test_bonus_alias_rolls_a_bonus_die(self, checker: CommandRunner, check_plugin: Plugin) -> None:
        content = await checker.run(handler(check_plugin, "rab"), "60", name="rab")

        assert "D100B=" in content
        assert "奖励骰:" in content

    async def test_penalty_alias_rolls_a_penalty_die(self, checker: CommandRunner, check_plugin: Plugin) -> None:
        content = await checker.run(handler(check_plugin, "rap"), "60", name="rap")

        assert "D100P=" in content
        assert "惩罚骰:" in content

    async def test_plain_check_is_unchanged(self, checker: CommandRunner, check_plugin: Plugin) -> None:
        """没有修饰骰时输出与从前一致，不出现方括号。"""

        content = await checker.run(handler(check_plugin, "ra"), "60", name="ra")

        assert re.search(r"D100=\d+/60，", content)
        assert "[" not in content

    async def test_a_leading_number_is_the_modifier_count(self, checker: CommandRunner, check_plugin: Plugin) -> None:
        """`.rab2 60` 剥掉别名后是 `2 60`，与 `.rab 2 60` 同形，都表示两个奖励骰。"""

        content = await checker.run(handler(check_plugin, "rab"), "2 60", name="rab")

        assert "D100B2=" in content
        assert "/60，" in content
        # 两个奖励骰即三个十位骰，基础骰之外还有两个。
        assert len(re.search(r"奖励骰:([\d ]+)", content).group(1).split()) == 2  # type: ignore[union-attr]

    async def test_the_count_may_be_followed_by_a_reason(self, checker: CommandRunner, check_plugin: Plugin) -> None:
        content = await checker.run(handler(check_plugin, "rap"), "3 60 侦查", name="rap")

        assert content.startswith("由于侦查，")
        assert "D100P3=" in content

    async def test_a_skill_value_is_not_mistaken_for_a_count(
        self, checker: CommandRunner, check_plugin: Plugin
    ) -> None:
        """`.rab 60 2` 的 60 是技能值：超出个数范围的数字按技能值处理。"""

        content = await checker.run(handler(check_plugin, "rab"), "60 2", name="rab")

        assert "D100B=" in content
        assert "/60，" in content

    async def test_a_reason_after_the_skill_is_untouched(self, checker: CommandRunner, check_plugin: Plugin) -> None:
        content = await checker.run(handler(check_plugin, "rab"), "60 侦查", name="rab")

        assert content.startswith("由于侦查，")
        assert "D100B=" in content

    @pytest.mark.parametrize("alias", ["rab", "rap", "rah", "rha", "rahb", "rahp", "rhab", "rhap"])
    def test_every_alias_is_registered(self, check_plugin: Plugin, alias: str) -> None:
        assert handler(check_plugin, alias) is not None


class TestHiddenCheck:
    async def test_result_goes_to_the_sender_privately(self, checker: CommandRunner, check_plugin: Plugin) -> None:
        reply = await checker.run(handler(check_plugin, "rah"), "60 侦查", name="rah", username="三无酱")

        assert len(checker.private_messages) == 1
        assert "D100=" in checker.private_messages[0]
        assert reply == "三无酱进行了一次暗检定（60）"

    async def test_is_rejected_in_private_chat(self, checker: CommandRunner, check_plugin: Plugin) -> None:
        with pytest.raises(CommandError, match="只能在群聊"):
            await checker.run(handler(check_plugin, "rah"), "60", name="rah", scene=Scene.C2C)

    async def test_the_group_learns_which_modifier_was_used(self, checker: CommandRunner, check_plugin: Plugin) -> None:
        """群内看不到结果，但应当能看到本次检定带了奖励骰。"""

        reply = await checker.run(handler(check_plugin, "rhab"), "60", name="rhab", username="三无酱")

        assert reply == "三无酱进行了一次暗检定（60，奖励骰）"
        assert "奖励骰:" in checker.private_messages[0]

    async def test_the_group_learns_how_many_modifier_dice_were_used(
        self, checker: CommandRunner, check_plugin: Plugin
    ) -> None:
        reply = await checker.run(handler(check_plugin, "rahp"), "2 60", name="rahp", username="三无酱")

        assert reply == "三无酱进行了一次暗检定（60，2 个惩罚骰）"


class TestRule:
    async def test_shows_the_current_rule(
        self, checker: CommandRunner, check_plugin: Plugin, rules: Mapping[str, CheckRule]
    ) -> None:
        """规则的名称与描述来自规则文件，故断言取自规则本身而非写死的措辞。"""

        content = await checker.run(handler(check_plugin, "rule"))

        assert rules["coc7"].name in content
        assert "大成功" in content

    async def test_switching_the_rule_persists_and_takes_effect(
        self, checker: CommandRunner, check_plugin: Plugin, rules: Mapping[str, CheckRule]
    ) -> None:
        """simple 规则只区分成功与失败，不会出现困难或极难。"""

        await checker.run(handler(check_plugin, "rule"), "simple")

        assert rules["simple"].name in await checker.run(handler(check_plugin, "rule"))

        content = await checker.run(handler(check_plugin, "ra"), "60", times=30)
        assert "困难成功" not in content
        assert "极难成功" not in content

    async def test_rejects_an_unknown_rule(
        self, checker: CommandRunner, check_plugin: Plugin, rules: Mapping[str, CheckRule]
    ) -> None:
        with pytest.raises(CommandError, match="没有这套检定规则"):
            await checker.run(handler(check_plugin, "rule"), "nonexistent")

        assert rules["coc7"].name in await checker.run(handler(check_plugin, "rule"))

    async def test_reports_a_rule_that_no_longer_exists(self, checker: CommandRunner, check_plugin: Plugin) -> None:
        """规则可能在升级后被移除，此时会话里存的标识已失效。"""

        state = await checker.store.get_chat_plugin_state(checker.chat.scene, checker.chat.openid, check_plugin.name)
        state.settings = {"rule": "removed"}

        with pytest.raises(CommandError, match="不存在"):
            await checker.run(handler(check_plugin, "ra"), "60")


class TestNickname:
    async def test_sets_the_nickname(self, namer: CommandRunner) -> None:
        await namer.run(nickname, "调查员")

        assert namer.member.nickname == "调查员"

    async def test_shows_the_current_name(self, namer: CommandRunner) -> None:
        namer.member.nickname = "调查员"

        assert "调查员" in await namer.run(nickname)

    async def test_clears_the_nickname(self, namer: CommandRunner) -> None:
        namer.member.nickname = "调查员"
        await namer.run(nickname, "清除")

        assert namer.member.nickname is None

    async def test_rejects_an_overlong_nickname(self, namer: CommandRunner) -> None:
        with pytest.raises(CommandError, match="最长"):
            await namer.run(nickname, "长" * (MAX_NICKNAME_LENGTH + 1))
