"""检定与昵称插件的测试。"""

from __future__ import annotations

from typing import Any

import pytest

from dicerobot.enums import Scene
from dicerobot.errors import CommandError
from dicerobot.plugins.check import hidden_check, show_or_set_rule, skill_check
from dicerobot.plugins.check import plugin as check_plugin
from dicerobot.plugins.nickname import MAX_NICKNAME_LENGTH, nickname
from dicerobot.plugins.nickname import plugin as nickname_plugin
from tests.conftest import CommandRunner


@pytest.fixture
async def checker(make_runner: Any) -> CommandRunner:
    return await make_runner(check_plugin)


@pytest.fixture
async def namer(make_runner: Any) -> CommandRunner:
    return await make_runner(nickname_plugin)


class TestSkillCheck:
    async def test_reports_the_roll_and_the_level(self, checker: CommandRunner) -> None:
        checker.member.nickname = "调查员"
        content = await checker.run(skill_check, "60")

        assert content.startswith("调查员进行了检定：D100=")
        assert "/60，" in content

    async def test_includes_the_reason(self, checker: CommandRunner) -> None:
        assert (await checker.run(skill_check, "60 侦查")).startswith("由于侦查，")

    async def test_each_repetition_occupies_its_own_line(self, checker: CommandRunner) -> None:
        content = await checker.run(skill_check, "60", times=3)

        # 首行为称呼，其后每次检定各占一行。
        assert len(content.splitlines()) == 4

    async def test_requires_a_skill_value(self, checker: CommandRunner) -> None:
        with pytest.raises(CommandError, match="请给出技能值"):
            await checker.run(skill_check, "侦查")


class TestHiddenCheck:
    async def test_result_goes_to_the_sender_privately(self, checker: CommandRunner) -> None:
        reply = await checker.run(hidden_check, "60 侦查", name="rah", username="三无酱")

        assert len(checker.private_messages) == 1
        assert "D100=" in checker.private_messages[0]
        assert reply == "三无酱进行了一次暗检定（60）"

    async def test_is_rejected_in_private_chat(self, checker: CommandRunner) -> None:
        with pytest.raises(CommandError, match="只能在群聊"):
            await checker.run(hidden_check, "60", name="rah", scene=Scene.C2C)


class TestRule:
    async def test_shows_the_current_rule(self, checker: CommandRunner) -> None:
        content = await checker.run(show_or_set_rule)

        assert "COC 第七版" in content
        assert "大成功" in content

    async def test_switching_the_rule_persists_and_takes_effect(self, checker: CommandRunner) -> None:
        """simple 规则只区分成功与失败，不会出现困难或极难。"""

        await checker.run(show_or_set_rule, "simple")

        assert "简单判定" in await checker.run(show_or_set_rule)

        content = await checker.run(skill_check, "60", times=30)
        assert "困难成功" not in content
        assert "极难成功" not in content

    async def test_rejects_an_unknown_rule(self, checker: CommandRunner) -> None:
        with pytest.raises(CommandError, match="没有这套检定规则"):
            await checker.run(show_or_set_rule, "nonexistent")

        assert "COC 第七版" in await checker.run(show_or_set_rule)

    async def test_reports_a_rule_that_no_longer_exists(self, checker: CommandRunner) -> None:
        """规则可能在升级后被移除，此时会话里存的标识已失效。"""

        state = await checker.store.get_chat_plugin_state(checker.chat.scene, checker.chat.openid, check_plugin.name)
        state.settings = {"rule": "removed"}

        with pytest.raises(CommandError, match="不存在"):
            await checker.run(skill_check, "60")


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
