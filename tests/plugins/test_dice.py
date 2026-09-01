"""掷骰插件的测试。

引擎本身已由 ``tests/trpg`` 覆盖，此处只验证插件层的职责：参数切分、错误转换、
设置的读写与多次掷骰的合并。
"""

from __future__ import annotations

import re
from typing import Any

import pytest

from dicerobot.enums import Scene
from dicerobot.errors import CommandError
from dicerobot.plugins.dice import (
    MAX_REPETITIONS,
    DiceChatSettings,
    default_surface,
    hidden_roll,
    plugin,
    roll,
)
from tests.conftest import CommandRunner


@pytest.fixture
async def runner(make_runner: Any) -> CommandRunner:
    return await make_runner(plugin)


class TestExpression:
    async def test_bare_command_rolls_the_default_die(self, runner: CommandRunner) -> None:
        assert "D100=" in await runner.run(roll)

    async def test_explicit_expression(self, runner: CommandRunner) -> None:
        assert re.search(r"3D6\+2=\(\d+\+\d+\+\d+\)\+2=\d+\+2=\d+", await runner.run(roll, "3d6+2"))

    async def test_supports_nested_arithmetic(self, runner: CommandRunner) -> None:
        assert "(2D6+3)×2=" in await runner.run(roll, "(2d6+3)*2")


class TestPresentation:
    async def test_includes_the_display_name(self, runner: CommandRunner) -> None:
        runner.member.nickname = "调查员"

        assert (await runner.run(roll)).startswith("调查员骰出了：")

    async def test_falls_back_to_the_platform_name(self, runner: CommandRunner) -> None:
        """未用 .nn 设置过时，用平台在群消息里给出的昵称。"""

        assert (await runner.run(roll, username="三无酱")).startswith("三无酱骰出了：")

    async def test_falls_back_to_a_generated_name(self, runner: CommandRunner) -> None:
        """单聊里平台给的昵称为空串，只能以 openid 末尾几位区分不同的人。"""

        assert (await runner.run(roll)).startswith("玩家0001骰出了：")

    async def test_the_configured_nickname_wins_over_the_platform_one(self, runner: CommandRunner) -> None:
        runner.member.nickname = "调查员"

        assert (await runner.run(roll, username="三无酱")).startswith("调查员骰出了：")

    async def test_reason_precedes_the_name(self, runner: CommandRunner) -> None:
        runner.member.nickname = "调查员"

        assert (await runner.run(roll, "1d100 侦查")).startswith("由于侦查，调查员骰出了：")


class TestReason:
    async def test_reason_without_an_expression_uses_the_default_die(self, runner: CommandRunner) -> None:
        """`.r 侦查` 应掷默认骰，而非报语法错误。"""

        assert "由于侦查，" in await runner.run(roll, "侦查")

    async def test_reason_starting_with_an_expression_character_is_not_misread(self, runner: CommandRunner) -> None:
        """`kick` 以 k 开头但既无数字也无 d，整段按理由处理。"""

        assert "由于kick，" in await runner.run(roll, "kick")

    async def test_a_real_typo_still_reports_an_error(self, runner: CommandRunner) -> None:
        """`3d6+` 含数字与 d，应判定为表达式并报错，而非按理由处理。"""

        with pytest.raises(CommandError, match="掷骰表达式"):
            await runner.run(roll, "3d6+")


class TestRepetition:
    async def test_each_roll_occupies_its_own_line(self, runner: CommandRunner) -> None:
        content = await runner.run(roll, "d100", times=3)

        # 首行为称呼，其后每次掷骰各占一行。
        assert len(content.splitlines()) == 4

    async def test_repeated_rolls_are_merged_into_one_message(self, runner: CommandRunner) -> None:
        """多次掷骰只消耗一条回复配额。"""

        await runner.run(roll, "d100", times=5)

        assert len(runner.client.calls) == 1

    async def test_rejects_excessive_repetition(self, runner: CommandRunner) -> None:
        with pytest.raises(CommandError, match="最多重复"):
            await runner.run(roll, "d100", times=MAX_REPETITIONS + 1)


class TestDefaultSurface:
    async def test_defaults_to_a_hundred_faces(self, runner: CommandRunner) -> None:
        assert "D100" in await runner.run(default_surface)

    async def test_setting_persists_and_affects_rolls(self, runner: CommandRunner) -> None:
        """设置写回 JSON 之后，下一次执行须读到新值。"""

        await runner.run(default_surface, "20")

        assert "D20" in await runner.run(default_surface)
        assert "D20=" in await runner.run(roll)

    @pytest.mark.parametrize("argument", ["0", "9999", "abc"])
    async def test_rejects_invalid_surfaces(self, runner: CommandRunner, argument: str) -> None:
        with pytest.raises(CommandError):
            await runner.run(default_surface, argument)

        assert "D100" in await runner.run(default_surface)

    async def test_settings_model_rejects_out_of_range_values(self) -> None:
        """存储里的非法取值应在读取时暴露，而不是流入求值。"""

        with pytest.raises(ValueError, match="less than or equal"):
            DiceChatSettings(default_surface=100_000)


class TestErrors:
    async def test_syntax_error_points_at_the_position(self, runner: CommandRunner) -> None:
        with pytest.raises(CommandError, match=r"第 5 个字符处.*括号没有闭合"):
            await runner.run(roll, "(1d6")

    async def test_unknown_character_starts_the_reason_rather_than_erroring(self, runner: CommandRunner) -> None:
        """表达式字符集之外的内容一律视为理由，与 `.r 1d100 侦查` 适用同一规则。"""

        assert "由于@2，" in await runner.run(roll, "1d6@2")

    async def test_limit_error_is_reported_in_plain_language(self, runner: CommandRunner) -> None:
        with pytest.raises(CommandError, match="最多掷"):
            await runner.run(roll, "500d6")

    async def test_division_by_zero_is_reported(self, runner: CommandRunner) -> None:
        with pytest.raises(CommandError, match="不能除以零"):
            await runner.run(roll, "1d6/0")


class TestHiddenRoll:
    async def test_result_goes_to_the_sender_privately(self, runner: CommandRunner) -> None:
        reply = await runner.run(hidden_roll, "1d100", name="rh")

        assert len(runner.private_messages) == 1
        assert "D100=" in runner.private_messages[0]
        assert "D100=" not in reply

    async def test_the_group_only_learns_what_was_rolled(self, runner: CommandRunner) -> None:
        """公开掷骰表达式、隐藏结果，是 TRPG 的惯例。"""

        reply = await runner.run(hidden_roll, "3d6+2 侦查", name="rh", username="三无酱")

        assert reply == "三无酱进行了一次暗骰（3d6+2）"

    async def test_repeated_rolls_stay_private(self, runner: CommandRunner) -> None:
        reply = await runner.run(hidden_roll, "1d100", name="rh", times=3)

        assert runner.private_messages[0].count("D100=") == 3
        assert "D100=" not in reply

    async def test_is_rejected_in_private_chat(self, runner: CommandRunner) -> None:
        """单聊里没有可隐藏的对象，且回复通道就是私聊本身。"""

        with pytest.raises(CommandError, match="只能在群聊"):
            await runner.run(hidden_roll, "1d100", name="rh", scene=Scene.C2C)
