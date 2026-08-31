"""日志中事件 ID 的测试。

事件 ID 由 loguru 的上下文携带：同一事件在各阶段产生的日志——包括插件自己打的——都
应带上它，而事件之外的日志不应多出这一列，事件之间也不得串扰。
"""

from __future__ import annotations

import asyncio
from collections.abc import Callable, Iterator
from pathlib import Path
from typing import cast

import pytest
from loguru import logger

from dicerobot.bot.context import EventContext
from dicerobot.bot.pipeline import Pipeline
from dicerobot.bot.plugin import Plugin
from dicerobot.bot.registry import Registry
from dicerobot.config import BotSettings, LogSettings
from dicerobot.logging import setup_logging
from dicerobot.qq.client import QQClient
from dicerobot.qq.enums import EventType
from dicerobot.qq.schemas import Payload
from dicerobot.storage import Database
from tests.conftest import RecordingClient


def join_payload(event_id: str, group_openid: str) -> Payload:
    return Payload(
        op=0,
        id=event_id,
        t="GROUP_ADD_ROBOT",
        d={"group_openid": group_openid, "op_member_openid": "U1"},
    )


def noisy_plugin(delay: float = 0.0) -> Plugin:
    """事件处理器只打一行日志，用于观察它落在哪个事件的上下文里。"""

    plugin = Plugin(name="noisy", display_name="noisy")

    @plugin.event(EventType.GROUP_ADD_ROBOT)
    async def on_join(context: EventContext) -> None:
        # 让并发的事件在处理中交错：上下文若被共用，两行日志会带上同一个 ID。
        await asyncio.sleep(delay)
        logger.info("插件正在处理 {}", context.event.scene_id)

    return plugin


@pytest.fixture
def log_lines(tmp_path: Path) -> Iterator[Callable[[], list[str]]]:
    """安装真实的日志配置，并返回读取已写出日志的函数。"""

    directory = tmp_path / "logs"
    setup_logging(LogSettings(level="DEBUG", directory=directory))

    def read() -> list[str]:
        # 文件 handler 以 enqueue 方式写入，移除 handler 时才会等待队列写完。
        logger.remove()

        return [line for path in directory.glob("*.log") for line in path.read_text(encoding="utf-8").splitlines()]

    yield read

    logger.remove()


async def dispatch(database: Database, plugin: Plugin, *payloads: Payload, workers: int = 1) -> None:
    """在真实流水线上处理若干事件，返回时它们均已处理完毕。"""

    registry = Registry()
    registry.add(plugin)
    pipeline = Pipeline(
        registry=registry,
        client=cast(QQClient, RecordingClient()),
        database=database,
        settings=BotSettings(workers=workers),
    )
    await pipeline.start()

    try:
        for payload in payloads:
            pipeline.submit(payload)
    finally:
        # 关停前会等待队列排空，因此无需另行等待。
        await pipeline.stop()


class TestFormat:
    def test_bound_event_id_is_written(self, log_lines: Callable[[], list[str]]) -> None:
        with logger.contextualize(event_id="EVENT_1"):
            logger.info("处理中")

        assert any("EVENT_1" in line for line in log_lines() if "处理中" in line)

    def test_logs_outside_an_event_keep_the_original_layout(self, log_lines: Callable[[], list[str]]) -> None:
        """启动、token 刷新等日志不属于任何事件，不应多出一个占位列。"""

        logger.info("已启动")

        line = next(line for line in log_lines() if "已启动" in line)

        assert line.count(" | ") == 2  # 时间 | 级别 | 位置 - 内容


class TestPipeline:
    async def test_plugin_logs_carry_the_event_id(self, log_lines: Callable[[], list[str]], database: Database) -> None:
        await dispatch(database, noisy_plugin(), join_payload("EVENT_JOIN", "G1"))

        line = next(line for line in log_lines() if "插件正在处理 G1" in line)

        assert "EVENT_JOIN" in line

    async def test_concurrent_events_do_not_share_the_id(
        self, log_lines: Callable[[], list[str]], database: Database
    ) -> None:
        """各 worker 是独立任务，contextvar 不会互相覆盖。"""

        await dispatch(
            database,
            noisy_plugin(delay=0.01),
            join_payload("EVENT_1", "G1"),
            join_payload("EVENT_2", "G2"),
            workers=2,
        )

        lines = log_lines()
        first = next(line for line in lines if "插件正在处理 G1" in line)
        second = next(line for line in lines if "插件正在处理 G2" in line)

        assert "EVENT_1" in first
        assert "EVENT_2" in second
