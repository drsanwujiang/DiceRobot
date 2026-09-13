"""WebSocket 网关客户端的测试。

以本地 ws 服务充当平台网关：连接、鉴权、心跳、断线与重连都真实发生，只有取接入点与取
access token 两个 HTTP 请求由 respx 打桩。心跳间隔由假网关下发，取毫秒级，测试因此不必
真实等待。
"""

from __future__ import annotations

import asyncio
import json
import time
from collections.abc import AsyncIterator, Awaitable, Callable, Iterator
from contextlib import asynccontextmanager
from typing import Any

import httpx
import pytest
import respx
from websockets.asyncio.server import ServerConnection, serve
from websockets.exceptions import ConnectionClosed

from dicerobot.qq import API_BASE_URL
from dicerobot.qq.client import QQClient
from dicerobot.qq.enums import CloseCode, OpCode
from dicerobot.qq.gateway import GatewayClient
from dicerobot.qq.schemas import Payload
from dicerobot.qq.token import ACCESS_TOKEN_URL, AccessTokenProvider

APP_ID = "102"
SECRET = "test-secret"
TOKEN = "token-1"
SESSION_ID = "SESSION_1"
GATEWAY_URL = f"{API_BASE_URL}/gateway"

# 心跳间隔由假网关下发。取值要大于单次收发的耗时，又要小到测试能在其间等到一次心跳。
HEARTBEAT_INTERVAL_MS = 60

# 一个事件帧的形状。sink 不解析 d，此处仍按真实字段填写，以免与平台形状脱节。
EVENT_DATA = {
    "id": "MSG_1",
    "group_openid": "G1",
    "author": {"member_openid": "U1"},
    "content": ".ping",
}

Script = Callable[[ServerConnection, int], Awaitable[None]]


class RecordingSink:
    """记录收到的事件。"""

    def __init__(self) -> None:
        self.payloads: list[Payload] = []

    def submit(self, payload: Payload) -> None:
        self.payloads.append(payload)


class FakeGateway:
    """充当平台网关的本地 ws 服务。

    每条连接都先走完开场：下发 Hello，收下鉴权帧，op 2 回 READY、op 6 回 RESUMED。之后
    本方的行为由 script 描述，同时持续记录上行帧并应答心跳。

    上行帧按连接分组记入 :attr:`frames`，故断线重连的用例可以分别断言两次连接发出的是
    鉴权还是恢复。
    """

    def __init__(self, script: Script, *, ack: bool = True, interval_ms: int = HEARTBEAT_INTERVAL_MS) -> None:
        """
        Args:
            script: 开场之后本方的行为，入参为连接与自 0 起计的连接序号。
            ack: 是否应答心跳。置否用于模拟对端静默。
            interval_ms: 下发的心跳间隔。
        """

        self._script = script
        self._ack = ack
        self._interval_ms = interval_ms
        self.frames: list[list[dict[str, Any]]] = []

    @property
    def connections(self) -> int:
        return len(self.frames)

    async def handle(self, connection: ServerConnection) -> None:
        index = self.connections
        frames: list[dict[str, Any]] = []
        self.frames.append(frames)

        try:
            await send(connection, {"op": OpCode.HELLO.value, "d": {"heartbeat_interval": self._interval_ms}})

            authentication = json.loads(await connection.recv())
            frames.append(authentication)

            if authentication["op"] == OpCode.RESUME:
                # 平台把 RESUMED 的 d 下发为字符串，客户端须容得下这个形状。
                await send(connection, {"op": OpCode.DISPATCH.value, "t": "RESUMED", "d": ""})
            else:
                await send(
                    connection,
                    {"op": OpCode.DISPATCH.value, "t": "READY", "d": {"session_id": SESSION_ID, "shard": [0, 1]}},
                )

            recorder = asyncio.create_task(self._record(connection, frames))

            try:
                await self._script(connection, index)
            finally:
                recorder.cancel()
                await asyncio.gather(recorder, return_exceptions=True)
        except ConnectionClosed:
            pass

    async def _record(self, connection: ServerConnection, frames: list[dict[str, Any]]) -> None:
        async for frame in connection:
            payload = json.loads(frame)
            frames.append(payload)

            if self._ack and payload["op"] == OpCode.HEARTBEAT:
                await send(connection, {"op": OpCode.HEARTBEAT_ACK.value, "d": None})


async def send(connection: ServerConnection, frame: dict[str, Any]) -> None:
    await connection.send(json.dumps(frame))


def event(seq: int, *, event_id: str = "EVENT_1") -> dict[str, Any]:
    return {
        "op": OpCode.DISPATCH.value,
        "s": seq,
        "t": "GROUP_AT_MESSAGE_CREATE",
        "id": event_id,
        "d": EVENT_DATA,
    }


async def hold(connection: ServerConnection, index: int) -> None:
    """保持连接，直至对端或测试把它关闭。

    不能改为无限等待：服务关停时会等各个 handler 返回，而关停只关连接，不会中断 handler
    自身的等待。
    """

    await connection.wait_closed()


async def wait_until(predicate: Callable[[], bool], *, timeout: float = 2.0) -> None:
    deadline = time.monotonic() + timeout

    while time.monotonic() < deadline:
        if predicate():
            return

        await asyncio.sleep(0.005)

    raise AssertionError("等待条件成立超时")


@pytest.fixture(autouse=True)
def no_proxy(monkeypatch: pytest.MonkeyPatch) -> None:
    # websockets 默认按环境变量选用代理。本机配置了代理时，假网关的地址会被送去代理而连不通。
    monkeypatch.setenv("NO_PROXY", "127.0.0.1")


@pytest.fixture
def sink() -> RecordingSink:
    return RecordingSink()


@pytest.fixture
def router() -> Iterator[respx.MockRouter]:
    with respx.mock(assert_all_called=False) as mock_router:
        mock_router.post(ACCESS_TOKEN_URL).mock(
            return_value=httpx.Response(200, json={"access_token": TOKEN, "expires_in": "7200"})
        )
        yield mock_router


@asynccontextmanager
async def running(
    gateway: FakeGateway,
    sink: RecordingSink,
    router: respx.MockRouter,
) -> AsyncIterator[None]:
    """把客户端接到假网关上，退出时先停客户端再关服务。"""

    async with serve(gateway.handle, "127.0.0.1", 0) as server:
        port = server.sockets[0].getsockname()[1]
        router.get(GATEWAY_URL).mock(return_value=httpx.Response(200, json={"url": f"ws://127.0.0.1:{port}"}))

        async with httpx.AsyncClient() as http_client:
            token_provider = AccessTokenProvider(app_id=APP_ID, secret=SECRET, client=http_client)
            client = QQClient(app_id=APP_ID, token_provider=token_provider, client=http_client)
            # 退避取毫秒级：重连本身是被测行为，不能让测试真的等上一秒。
            gateway_client = GatewayClient(
                client=client,
                token_provider=token_provider,
                sink=sink,
                initial_reconnect_delay=0.01,
                max_reconnect_delay=0.01,
            )

            await gateway_client.start()

            try:
                yield
            finally:
                await gateway_client.stop()


class TestAuthentication:
    async def test_identify_carries_the_token_intents_and_shard(
        self, sink: RecordingSink, router: respx.MockRouter
    ) -> None:
        gateway = FakeGateway(hold)

        async with running(gateway, sink, router):
            await wait_until(lambda: bool(gateway.frames) and bool(gateway.frames[0]))

            identify = gateway.frames[0][0]

        assert identify["op"] == OpCode.IDENTIFY
        # 群机器人的凭据与 OpenAPI 的 Authorization 头同形，不是旧版的 Bot appid.token。
        assert identify["d"]["token"] == f"QQBot {TOKEN}"
        # 群/单聊的全部事件都在这一位内。
        assert identify["d"]["intents"] == 1 << 25
        assert identify["d"]["shard"] == [0, 1]


class TestDispatch:
    async def test_events_reach_the_sink(self, sink: RecordingSink, router: respx.MockRouter) -> None:
        async def script(connection: ServerConnection, index: int) -> None:
            await send(connection, event(1))
            await hold(connection, index)

        async with running(FakeGateway(script), sink, router):
            await wait_until(lambda: len(sink.payloads) == 1)

        payload = sink.payloads[0]

        assert payload.t == "GROUP_AT_MESSAGE_CREATE"
        assert payload.id == "EVENT_1"
        assert payload.s == 1
        assert payload.d["content"] == ".ping"

    async def test_session_events_are_not_forwarded(self, sink: RecordingSink, router: respx.MockRouter) -> None:
        """READY 与 RESUMED 只描述连接自身，运行时无从处理，不应投给它。"""

        async def script(connection: ServerConnection, index: int) -> None:
            await send(connection, event(1))
            await hold(connection, index)

        async with running(FakeGateway(script), sink, router):
            await wait_until(lambda: len(sink.payloads) == 1)
            # 再等一会儿，READY 若被转发，此时早已到达。
            await asyncio.sleep(0.05)

        assert [payload.t for payload in sink.payloads] == ["GROUP_AT_MESSAGE_CREATE"]


class TestHeartbeat:
    async def test_carries_the_last_seq(self, sink: RecordingSink, router: respx.MockRouter) -> None:
        """心跳的 d 是最后收到的 s，平台据此判断客户端落后多少。"""

        async def script(connection: ServerConnection, index: int) -> None:
            await send(connection, event(7))
            await hold(connection, index)

        gateway = FakeGateway(script)

        async with running(gateway, sink, router):
            await wait_until(
                lambda: bool(gateway.frames) and any(frame["op"] == OpCode.HEARTBEAT for frame in gateway.frames[0])
            )

            heartbeat = next(frame for frame in gateway.frames[0] if frame["op"] == OpCode.HEARTBEAT)

        assert heartbeat["d"] == 7

    async def test_silence_triggers_a_reconnect(self, sink: RecordingSink, router: respx.MockRouter) -> None:
        """对端不再应答时须自行断开：TCP 半开不会报错，等内核放弃重传是十几分钟。"""

        gateway = FakeGateway(hold, ack=False, interval_ms=20)

        async with running(gateway, sink, router):
            await wait_until(lambda: gateway.connections >= 2)


class TestReconnect:
    async def test_expired_connection_resumes_the_session(self, sink: RecordingSink, router: respx.MockRouter) -> None:
        """会话仍可续传时须 RESUME：平台会补发遗漏的事件，重新鉴权则会丢掉它们。"""

        async def script(connection: ServerConnection, index: int) -> None:
            if index > 0:
                await hold(connection, index)

                return

            await send(connection, event(5))
            # 连接过期，但会话未失效。
            await connection.close(code=CloseCode.SESSION_EXPIRED)

        gateway = FakeGateway(script)

        async with running(gateway, sink, router):
            await wait_until(lambda: gateway.connections >= 2 and bool(gateway.frames[1]))

            resume = gateway.frames[1][0]

        assert resume["op"] == OpCode.RESUME
        assert resume["d"]["session_id"] == SESSION_ID
        assert resume["d"]["seq"] == 5

    async def test_reconnect_request_resumes_the_session(self, sink: RecordingSink, router: respx.MockRouter) -> None:
        """op 7 只是要求换一条连接，会话并未失效，故重连后仍是 RESUME。"""

        async def script(connection: ServerConnection, index: int) -> None:
            if index > 0:
                await hold(connection, index)

                return

            await send(connection, event(5))
            await send(connection, {"op": OpCode.RECONNECT.value, "d": None})
            await hold(connection, index)

        gateway = FakeGateway(script)

        async with running(gateway, sink, router):
            await wait_until(lambda: gateway.connections >= 2 and bool(gateway.frames[1]))

            resume = gateway.frames[1][0]

        assert resume["op"] == OpCode.RESUME
        assert resume["d"]["seq"] == 5

    async def test_invalid_session_close_code_forces_a_fresh_identify(
        self, sink: RecordingSink, router: respx.MockRouter
    ) -> None:
        """session_id 已失效时续传只会再被拒，须丢掉会话重新鉴权。"""

        async def script(connection: ServerConnection, index: int) -> None:
            if index > 0:
                await hold(connection, index)

                return

            await send(connection, event(5))
            await connection.close(code=CloseCode.INVALID_SESSION)

        gateway = FakeGateway(script)

        async with running(gateway, sink, router):
            await wait_until(lambda: gateway.connections >= 2 and bool(gateway.frames[1]))

            frame = gateway.frames[1][0]

        assert frame["op"] == OpCode.IDENTIFY

    async def test_invalid_session_frame_forces_a_fresh_identify(
        self, sink: RecordingSink, router: respx.MockRouter
    ) -> None:
        """op 9 同样表示会话不可用，与收到 4006 关闭码的处置一致。"""

        async def script(connection: ServerConnection, index: int) -> None:
            if index > 0:
                await hold(connection, index)

                return

            await send(connection, event(5))
            await send(connection, {"op": OpCode.INVALID_SESSION.value, "d": False})
            await hold(connection, index)

        gateway = FakeGateway(script)

        async with running(gateway, sink, router):
            await wait_until(lambda: gateway.connections >= 2 and bool(gateway.frames[1]))

            frame = gateway.frames[1][0]

        assert frame["op"] == OpCode.IDENTIFY

    async def test_rejected_intents_stop_reconnecting(self, sink: RecordingSink, router: respx.MockRouter) -> None:
        """重连不会让平台改变主意，只会白耗连接配额。"""

        async def script(connection: ServerConnection, index: int) -> None:
            await connection.close(code=CloseCode.INTENTS_NOT_AUTHORIZED)

        gateway = FakeGateway(script)

        async with running(gateway, sink, router):
            # 退避是 10 毫秒，若仍在重连，这段时间足够连上许多次。
            await asyncio.sleep(0.2)

        assert gateway.connections == 1
