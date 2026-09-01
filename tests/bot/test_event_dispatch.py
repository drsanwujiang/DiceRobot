"""事件派发的测试。

关注流水线在多个插件同时响应一个事件时的行为：输出如何合并、单个插件出错是否波及
其余插件、三层开关是否同样生效。
"""

from __future__ import annotations

import asyncio
from collections.abc import AsyncIterator
from typing import Any, cast

import pytest

from dicerobot.bot.context import EventContext
from dicerobot.bot.pipeline import Pipeline
from dicerobot.bot.plugin import Plugin
from dicerobot.bot.registry import Registry
from dicerobot.config import BotSettings
from dicerobot.enums import Scene
from dicerobot.qq.client import QQClient
from dicerobot.qq.enums import EventType
from dicerobot.qq.schemas import Payload
from dicerobot.storage import Database, Store
from tests.conftest import RecordingClient

JOIN_PAYLOAD = Payload(
    op=0,
    id="EVENT_JOIN",
    t="GROUP_ADD_ROBOT",
    d={"group_openid": "G1", "op_member_openid": "U1"},
)


def greeter(name: str, text: str) -> Plugin:
    plugin = Plugin(name=name, display_name=name)

    @plugin.event(EventType.GROUP_ADD_ROBOT)
    async def on_join(context: EventContext) -> None:
        context.write(text)

    return plugin


class PipelineHarness:
    def __init__(self, client: RecordingClient, pipeline: Pipeline) -> None:
        self.client = client
        self.pipeline = pipeline

    async def dispatch(self, payload: Payload = JOIN_PAYLOAD) -> None:
        """投递一个事件并等待处理完成。"""

        self.pipeline.submit(payload)
        await asyncio.sleep(0.05)


@pytest.fixture
async def make_harness(database: Database) -> AsyncIterator[Any]:
    harnesses: list[Pipeline] = []

    async def factory(*plugins: Plugin, workers: int = 1) -> PipelineHarness:
        registry = Registry()

        for plugin in plugins:
            registry.add(plugin)

        client = RecordingClient()
        pipeline = Pipeline(
            registry=registry,
            client=cast(QQClient, client),
            database=database,
            settings=BotSettings(workers=workers),
        )
        await pipeline.start()
        harnesses.append(pipeline)

        return PipelineHarness(client, pipeline)

    yield factory

    for pipeline in harnesses:
        await pipeline.stop()


class TestMultipleHandlers:
    async def test_output_is_merged_into_one_reply(self, make_harness: Any) -> None:
        """多个插件共用一个回复会话，只消耗一条配额。"""

        harness = await make_harness(greeter("first", "第一句"), greeter("second", "第二句"))
        await harness.dispatch()

        assert len(harness.client.calls) == 1
        assert harness.client.calls[0]["content"] == "第一句\n第二句"

    async def test_reply_carries_the_event_id(self, make_harness: Any) -> None:
        harness = await make_harness(greeter("first", "你好"))
        await harness.dispatch()

        call = harness.client.calls[0]
        assert call["event_id"] == "EVENT_JOIN"
        assert call["msg_id"] is None

    async def test_a_failing_plugin_does_not_stop_the_others(self, make_harness: Any) -> None:
        broken = Plugin(name="broken", display_name="broken")

        @broken.event(EventType.GROUP_ADD_ROBOT)
        async def explode(context: EventContext) -> None:
            raise RuntimeError("boom")

        harness = await make_harness(broken, greeter("second", "第二句"))
        await harness.dispatch()

        assert harness.client.calls[0]["content"] == "第二句"

    async def test_failures_produce_no_error_reply(self, make_harness: Any) -> None:
        """事件不是用户发起的，出错不应在入群等场景下连续发送无效回复。"""

        broken = Plugin(name="broken", display_name="broken")

        @broken.event(EventType.GROUP_ADD_ROBOT)
        async def explode(context: EventContext) -> None:
            raise RuntimeError("boom")

        harness = await make_harness(broken)
        await harness.dispatch()

        assert harness.client.calls == []


class TestEnablement:
    async def test_a_disabled_plugin_does_not_handle_events(self, make_harness: Any, database: Database) -> None:
        harness = await make_harness(greeter("first", "第一句"), greeter("second", "第二句"))

        async with database.session() as session:
            store = Store(session)
            await store.get_chat(Scene.GROUP, "G1")
            state = await store.get_chat_plugin_state(Scene.GROUP, "G1", "first")
            state.enabled = False

        await harness.dispatch()

        assert harness.client.calls[0]["content"] == "第二句"

    async def test_a_disabled_chat_silences_every_handler(self, make_harness: Any, database: Database) -> None:
        harness = await make_harness(greeter("first", "第一句"))

        async with database.session() as session:
            (await Store(session).get_chat(Scene.GROUP, "G1")).enabled = False

        await harness.dispatch()

        assert harness.client.calls == []


class TestUnhandledEvents:
    async def test_event_without_handlers_sends_nothing(self, make_harness: Any) -> None:
        harness = await make_harness(greeter("first", "第一句"))
        await harness.dispatch(Payload(op=0, id="EVENT_LEAVE", t="GROUP_DEL_ROBOT", d={"group_openid": "G1"}))

        assert harness.client.calls == []

    async def test_unknown_event_type_is_skipped(self, make_harness: Any) -> None:
        harness = await make_harness(greeter("first", "第一句"))
        await harness.dispatch(Payload(op=0, id="E1", t="SOMETHING_NEW", d={}))

        assert harness.client.calls == []


class TestConcurrency:
    async def test_workers_process_events_in_parallel(self, make_harness: Any, database: Database) -> None:
        """worker 是并发槽位：一条指令的耗时几乎都在等待平台响应，串行处理会成为吞吐瓶颈。"""

        # 先建好两个会话涉及的记录：惰性创建是写操作，会在 handler 执行期间一直握着 SQLite
        # 的写锁，两个事件将因此串行，测不出 worker 的并发。
        async with database.session() as session:
            store = Store(session)
            await store.get_plugin_state("barrier")

            for openid in ("G1", "G2"):
                await store.get_chat(Scene.GROUP, openid)
                await store.get_chat_plugin_state(Scene.GROUP, openid, "barrier")

        # 两个 handler 都到达栅栏才能继续，只有一个 worker 时后者永远等不到前者。
        barrier = asyncio.Barrier(2)
        plugin = Plugin(name="barrier", display_name="barrier")
        passed = 0

        @plugin.event(EventType.GROUP_ADD_ROBOT)
        async def wait_for_each_other(context: EventContext) -> None:
            nonlocal passed

            async with asyncio.timeout(1):
                await barrier.wait()

            passed += 1

        harness = await make_harness(plugin, workers=2)
        harness.pipeline.submit(JOIN_PAYLOAD)
        harness.pipeline.submit(Payload(op=0, id="EVENT_JOIN_2", t="GROUP_ADD_ROBOT", d={"group_openid": "G2"}))
        await harness.pipeline.stop()

        assert passed == 2
