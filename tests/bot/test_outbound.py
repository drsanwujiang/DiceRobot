"""被动回复配额的测试。

超限时平台静默拒绝，问题只在真实环境暴露，因此边界值需由测试固定。
"""

from __future__ import annotations

from datetime import UTC, datetime, timedelta
from typing import Any, cast

import pytest

from dicerobot.bot.message import IncomingMessage
from dicerobot.bot.outbound import QUOTAS, ReplyBuffer, ReplySession
from dicerobot.enums import Scene
from dicerobot.errors import ReplyQuotaExhaustedError, ReplyWindowExpiredError
from dicerobot.qq.client import QQClient

RECEIVED_AT = datetime(2026, 1, 1, tzinfo=UTC)


class RecordingClient:
    """按场景分别记录调用参数的替身客户端。"""

    def __init__(self) -> None:
        self.group_calls: list[dict[str, Any]] = []
        self.c2c_calls: list[dict[str, Any]] = []

    async def send_group_message(self, **kwargs: Any) -> None:
        self.group_calls.append(kwargs)

    async def send_c2c_message(self, **kwargs: Any) -> None:
        self.c2c_calls.append(kwargs)


class FakeClock:
    def __init__(self, now: datetime = RECEIVED_AT) -> None:
        self.now = now

    def __call__(self) -> datetime:
        return self.now

    def advance(self, **kwargs: float) -> None:
        self.now += timedelta(**kwargs)


def make_message(scene: Scene = Scene.GROUP) -> IncomingMessage:
    return IncomingMessage(
        scene=scene,
        scene_id="SCENE_1",
        sender_id="USER_1",
        content=".ping",
        message_id="MSG_1",
        received_at=RECEIVED_AT,
    )


@pytest.fixture
def client() -> RecordingClient:
    return RecordingClient()


@pytest.fixture
def clock() -> FakeClock:
    return FakeClock()


def make_session(client: RecordingClient, clock: FakeClock, scene: Scene = Scene.GROUP) -> ReplySession:
    return ReplySession(client=cast(QQClient, client), target=make_message(scene).reply_target, now=clock)


class TestQuotaValues:
    def test_group_allows_five_replies_in_five_minutes(self) -> None:
        assert QUOTAS[Scene.GROUP].max_replies == 5
        assert QUOTAS[Scene.GROUP].window == timedelta(minutes=5)

    def test_c2c_allows_four_replies_in_sixty_minutes(self) -> None:
        assert QUOTAS[Scene.C2C].max_replies == 4
        assert QUOTAS[Scene.C2C].window == timedelta(minutes=60)


class TestReplySession:
    async def test_sequence_number_starts_at_one_and_increments(
        self, client: RecordingClient, clock: FakeClock
    ) -> None:
        """相同的 msg_id 与 msg_seq 组合会被平台拒绝，序号须逐条递增。"""

        session = make_session(client, clock)

        await session.send("一")
        await session.send("二")

        assert [call["msg_seq"] for call in client.group_calls] == [1, 2]
        assert all(call["msg_id"] == "MSG_1" for call in client.group_calls)

    async def test_routes_group_message_to_group_endpoint(self, client: RecordingClient, clock: FakeClock) -> None:
        await make_session(client, clock, Scene.GROUP).send("hi")

        assert len(client.group_calls) == 1
        assert client.group_calls[0]["group_openid"] == "SCENE_1"
        assert not client.c2c_calls

    async def test_routes_c2c_message_to_user_endpoint(self, client: RecordingClient, clock: FakeClock) -> None:
        await make_session(client, clock, Scene.C2C).send("hi")

        assert len(client.c2c_calls) == 1
        assert client.c2c_calls[0]["openid"] == "SCENE_1"
        assert not client.group_calls

    async def test_raises_once_group_quota_is_exhausted(self, client: RecordingClient, clock: FakeClock) -> None:
        session = make_session(client, clock, Scene.GROUP)

        for _ in range(5):
            await session.send("ok")

        assert session.remaining == 0

        with pytest.raises(ReplyQuotaExhaustedError):
            await session.send("第六条")

        assert len(client.group_calls) == 5

    async def test_raises_once_c2c_quota_is_exhausted(self, client: RecordingClient, clock: FakeClock) -> None:
        session = make_session(client, clock, Scene.C2C)

        for _ in range(4):
            await session.send("ok")

        with pytest.raises(ReplyQuotaExhaustedError):
            await session.send("第五条")

    async def test_raises_once_group_window_has_passed(self, client: RecordingClient, clock: FakeClock) -> None:
        session = make_session(client, clock, Scene.GROUP)

        clock.advance(minutes=4, seconds=59)
        await session.send("还来得及")

        clock.advance(seconds=1)
        assert session.expired

        with pytest.raises(ReplyWindowExpiredError):
            await session.send("太晚了")

    async def test_c2c_window_is_much_longer(self, client: RecordingClient, clock: FakeClock) -> None:
        session = make_session(client, clock, Scene.C2C)

        clock.advance(minutes=30)
        await session.send("单聊有 60 分钟")

        assert len(client.c2c_calls) == 1

    async def test_failed_send_still_consumes_quota(self, clock: FakeClock) -> None:
        """发送失败时无法确定消息是否送达，故序号照常递增以避免重复消息。"""

        class FailingOnceClient(RecordingClient):
            async def send_group_message(self, **kwargs: Any) -> None:
                await super().send_group_message(**kwargs)

                if len(self.group_calls) == 1:
                    raise RuntimeError("boom")

        client = FailingOnceClient()
        session = make_session(client, clock)

        with pytest.raises(RuntimeError):
            await session.send("一")

        assert session.remaining == 4

        # 重试换用新序号，复用已发出的序号会被平台判为重复。
        await session.send("二")
        assert [call["msg_seq"] for call in client.group_calls] == [1, 2]


class TestReplyBuffer:
    async def test_merges_writes_into_a_single_message(self, client: RecordingClient, clock: FakeClock) -> None:
        """合并输出是节省配额的主要手段。"""

        buffer = ReplyBuffer(make_session(client, clock))

        buffer.write("第一行")
        buffer.write("第二行")
        buffer.write("第三行")

        assert await buffer.flush() is True
        assert len(client.group_calls) == 1
        assert client.group_calls[0]["content"] == "第一行\n第二行\n第三行"

    async def test_empty_flush_does_not_consume_quota(self, client: RecordingClient, clock: FakeClock) -> None:
        session = make_session(client, clock)
        buffer = ReplyBuffer(session)

        assert await buffer.flush() is False
        assert not client.group_calls
        assert session.remaining == 5

    @pytest.mark.parametrize("text", ["", "   ", "\n"])
    async def test_blank_writes_are_ignored(self, client: RecordingClient, clock: FakeClock, text: str) -> None:
        buffer = ReplyBuffer(make_session(client, clock))
        buffer.write(text)

        assert buffer.pending is False
        assert await buffer.flush() is False

    async def test_flush_clears_the_buffer(self, client: RecordingClient, clock: FakeClock) -> None:
        buffer = ReplyBuffer(make_session(client, clock))

        buffer.write("一")
        await buffer.flush()
        buffer.write("二")
        await buffer.flush()

        assert [call["content"] for call in client.group_calls] == ["一", "二"]

    async def test_clear_discards_pending_output(self, client: RecordingClient, clock: FakeClock) -> None:
        buffer = ReplyBuffer(make_session(client, clock))

        buffer.write("这段会被丢弃")
        buffer.clear()

        assert await buffer.flush() is False
