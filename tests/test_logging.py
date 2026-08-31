"""事件 ID 与阶段耗时的日志测试。

事件 ID 由 loguru 的上下文携带：同一事件在各阶段产生的日志——包括插件自己打的——都
应带上它，而事件之外的日志不应多出这一列，事件之间也不得串扰。

webhook 与 worker 各记录一对开始、结束日志，结束时记录本阶段耗时，据此可还原一条消息
在各阶段的耗时。
"""

from __future__ import annotations

import asyncio
from collections.abc import Callable, Iterator
from pathlib import Path
from typing import cast

import httpx
import pytest
from fastapi import FastAPI
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
from dicerobot.qq.webhook import create_webhook_router
from dicerobot.storage import Database
from tests.conftest import RecordingClient
from tests.test_app import SECRET, WEBHOOK_PATH, group_message_payload, post_event


class NullSink:
    """仅用于满足 EventSink 协议：webhook 阶段的耗时与后续处理无关。"""

    def submit(self, payload: Payload) -> None:
        pass


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


class TestTiming:
    async def test_webhook_records_its_own_duration(self, log_lines: Callable[[], list[str]]) -> None:
        app = FastAPI()
        app.include_router(create_webhook_router(path=WEBHOOK_PATH, secret=SECRET, sink=NullSink()))
        transport = httpx.ASGITransport(app=app)

        async with httpx.AsyncClient(transport=transport, base_url="http://test") as client:
            response = await post_event(client, group_message_payload(".r"))

        assert response.status_code == 200
        assert any("webhook 处理完成，耗时" in line and "EVENT_1" in line for line in log_lines())

    async def test_worker_records_queue_wait_and_duration(
        self, log_lines: Callable[[], list[str]], database: Database
    ) -> None:
        await dispatch(database, noisy_plugin(), join_payload("EVENT_JOIN", "G1"))

        lines = [line for line in log_lines() if "EVENT_JOIN" in line]

        assert any("开始处理事件，排队耗时" in line for line in lines)
        assert any("事件处理完成，耗时" in line for line in lines)

    async def test_duration_is_recorded_when_processing_fails(
        self, log_lines: Callable[[], list[str]], database: Database
    ) -> None:
        """处理失败的事件往往耗时最长，结束日志不能因异常而丢失。"""

        # 缺少 group_openid，归一化时抛出 ValidationError。
        await dispatch(database, noisy_plugin(), Payload(op=0, id="EVENT_BAD", t="GROUP_ADD_ROBOT", d={}))

        lines = [line for line in log_lines() if "EVENT_BAD" in line]

        assert any("处理事件时发生未捕获的异常" in line for line in lines)
        assert any("事件处理完成，耗时" in line for line in lines)
