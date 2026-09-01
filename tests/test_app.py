"""端到端链路测试。

覆盖「平台推送 -> 验签 -> 去重 -> 入队 -> 路由 -> 被动回复」的完整路径，含鉴权头的
拼装。除 HTTP 出站由 respx 拦截外，其余环节均为真实执行。
"""

from __future__ import annotations

import asyncio
import json
import time
from collections.abc import AsyncIterator, Callable, Iterator
from pathlib import Path
from typing import Any

import httpx
import pytest
import respx
from cryptography.hazmat.primitives.asymmetric.ed25519 import Ed25519PrivateKey
from pydantic import SecretStr

from dicerobot.app import create_app
from dicerobot.config import BotSettings, LogSettings, QQSettings, Settings
from dicerobot.qq import API_BASE_URL
from dicerobot.qq.signature import SIGNATURE_HEADER, TIMESTAMP_HEADER
from dicerobot.qq.token import ACCESS_TOKEN_URL

SECRET = "test-secret"
APP_ID = "102"
WEBHOOK_PATH = "/qq/webhook"
# 取自被测代码，域名变动时测试随之改变，不会静默地打到真实平台。
SEND_GROUP_URL = f"{API_BASE_URL}/v2/groups/G1/messages"


def sign(timestamp: str, body: bytes) -> str:
    seed = SECRET.encode()

    while len(seed) < 32:
        seed *= 2

    return Ed25519PrivateKey.from_private_bytes(seed[:32]).sign(timestamp.encode() + body).hex()


def group_message_payload(
    content: str,
    *,
    event_id: str = "EVENT_1",
    message_id: str = "MSG_1",
    mentions: list[dict[str, Any]] | None = None,
) -> dict[str, Any]:
    data: dict[str, Any] = {
        "id": message_id,
        "group_openid": "G1",
        "author": {"member_openid": "U1", "member_role": "owner"},
        "content": content,
    }

    if mentions is not None:
        data["mentions"] = mentions

    return {"op": 0, "id": event_id, "t": "GROUP_AT_MESSAGE_CREATE", "d": data}


async def wait_until(predicate: Callable[[], bool], *, timeout: float = 2.0) -> None:
    deadline = time.monotonic() + timeout

    while time.monotonic() < deadline:
        if predicate():
            return

        await asyncio.sleep(0.005)

    raise AssertionError("等待条件成立超时")


@pytest.fixture
def settings(tmp_path: Path) -> Settings:
    return Settings(
        debug=False,
        webhook_path=WEBHOOK_PATH,
        database_url=f"sqlite+aiosqlite:///{tmp_path.as_posix()}/test.db",
        qq=QQSettings(app_id=APP_ID, secret=SecretStr(SECRET)),
        bot=BotSettings(workers=1),
        log=LogSettings(level="WARNING", directory=tmp_path / "logs"),
    )


@pytest.fixture
def router() -> Iterator[respx.MockRouter]:
    # assert_all_mocked=False 使未匹配的请求透传至真实 transport，
    # 即测试客户端所用的 ASGI transport。
    with respx.mock(assert_all_called=False, assert_all_mocked=False) as mock_router:
        mock_router.post(ACCESS_TOKEN_URL).mock(
            return_value=httpx.Response(200, json={"access_token": "token-1", "expires_in": "7200"})
        )
        yield mock_router


@pytest.fixture
async def client(settings: Settings, router: respx.MockRouter) -> AsyncIterator[httpx.AsyncClient]:
    # 依赖 router：lifespan 会预取 access token，mock 须在创建应用之前安装，
    # 否则请求会穿透到真实平台。
    app = create_app(settings)

    async with app.router.lifespan_context(app):
        transport = httpx.ASGITransport(app=app)

        async with httpx.AsyncClient(transport=transport, base_url="http://test") as http_client:
            yield http_client


async def post_event(client: httpx.AsyncClient, payload: dict[str, Any]) -> httpx.Response:
    raw = json.dumps(payload).encode()
    timestamp = "1730000000"

    return await client.post(
        WEBHOOK_PATH,
        content=raw,
        headers={
            SIGNATURE_HEADER: sign(timestamp, raw),
            TIMESTAMP_HEADER: timestamp,
            "Content-Type": "application/json",
        },
    )


class TestCallbackValidation:
    async def test_responds_with_a_verifiable_signature(self, client: httpx.AsyncClient) -> None:
        payload = {"op": 13, "d": {"plain_token": "TOKEN", "event_ts": "1730000000"}}

        response = await post_event(client, payload)

        assert response.status_code == httpx.codes.OK

        body = response.json()
        assert body["plain_token"] == "TOKEN"

        seed = SECRET.encode()
        while len(seed) < 32:
            seed *= 2

        Ed25519PrivateKey.from_private_bytes(seed[:32]).public_key().verify(
            bytes.fromhex(body["signature"]), b"1730000000TOKEN"
        )


class TestSignature:
    async def test_rejects_a_tampered_body(self, client: httpx.AsyncClient) -> None:
        raw = json.dumps(group_message_payload(".ping")).encode()

        response = await client.post(
            WEBHOOK_PATH,
            content=raw.replace(b".ping", b".pong"),
            headers={SIGNATURE_HEADER: sign("1730000000", raw), TIMESTAMP_HEADER: "1730000000"},
        )

        assert response.status_code == httpx.codes.UNAUTHORIZED

    async def test_rejects_a_missing_signature(self, client: httpx.AsyncClient) -> None:
        response = await client.post(WEBHOOK_PATH, json=group_message_payload(".ping"))

        assert response.status_code == httpx.codes.UNAUTHORIZED


class TestDispatch:
    async def test_a_command_aimed_at_another_bot_is_ignored(
        self, client: httpx.AsyncClient, router: respx.MockRouter
    ) -> None:
        """群里可能有多个骰子机器人，@ 的不是自己就不应响应。"""

        route = router.post(SEND_GROUP_URL).mock(return_value=httpx.Response(200, json={"id": "REPLY_1"}))

        await post_event(
            client,
            group_message_payload("<@OTHERBOT> .ping", mentions=[{"member_openid": "OTHERBOT", "is_you": False}]),
        )
        await asyncio.sleep(0.05)

        assert route.call_count == 0

    async def test_a_command_mentioning_us_is_answered(
        self, client: httpx.AsyncClient, router: respx.MockRouter
    ) -> None:
        route = router.post(SEND_GROUP_URL).mock(return_value=httpx.Response(200, json={"id": "REPLY_1"}))

        await post_event(
            client,
            group_message_payload(
                "<@OTHERBOT> <@US> .ping",
                mentions=[{"member_openid": "OTHERBOT", "is_you": False}, {"member_openid": "US", "is_you": True}],
            ),
        )

        await wait_until(lambda: route.call_count == 1)

    async def test_ping_produces_a_passive_reply(self, client: httpx.AsyncClient, router: respx.MockRouter) -> None:
        route = router.post(SEND_GROUP_URL).mock(return_value=httpx.Response(200, json={"id": "REPLY_1"}))

        response = await post_event(client, group_message_payload(".ping"))

        # webhook 立即返回，处理为异步。
        assert response.status_code == httpx.codes.OK

        await wait_until(lambda: route.call_count == 1)

        request = route.calls.last.request
        assert json.loads(request.read()) == {
            "msg_type": 0,
            "content": "pong",
            "msg_id": "MSG_1",
            "msg_seq": 1,
        }
        assert request.headers["Authorization"] == "QQBot token-1"
        assert request.headers["X-Union-Appid"] == APP_ID

    async def test_roll_command_reaches_the_platform(self, client: httpx.AsyncClient, router: respx.MockRouter) -> None:
        """掷骰的完整链路：解析 -> 求值 -> 合并 -> 被动回复。"""

        route = router.post(SEND_GROUP_URL).mock(return_value=httpx.Response(200, json={}))

        await post_event(client, group_message_payload(".r 3d6+2 侦查#3"))
        await wait_until(lambda: route.call_count == 1)

        content = json.loads(route.calls.last.request.read())["content"]
        lines = content.splitlines()

        # 三次掷骰合并为一条消息，只消耗一条回复配额。
        assert route.call_count == 1
        assert lines[0] == "由于侦查，玩家U1骰出了："
        assert len(lines) == 4
        assert all(line.startswith("3D6+2=") for line in lines[1:])

    async def test_leading_space_from_the_mention_is_stripped(
        self, client: httpx.AsyncClient, router: respx.MockRouter
    ) -> None:
        """@ 机器人的消息正文以空格开头，未清理则无法匹配指令前缀。"""

        route = router.post(SEND_GROUP_URL).mock(return_value=httpx.Response(200, json={}))

        await post_event(client, group_message_payload(" .ping"))
        await wait_until(lambda: route.call_count == 1)

    async def test_repeated_event_is_only_handled_once(
        self, client: httpx.AsyncClient, router: respx.MockRouter
    ) -> None:
        """平台重推同一事件时不得重复回复。"""

        route = router.post(SEND_GROUP_URL).mock(return_value=httpx.Response(200, json={}))
        payload = group_message_payload(".ping", event_id="EVENT_DUP")

        await post_event(client, payload)
        await wait_until(lambda: route.call_count == 1)

        await post_event(client, payload)
        await asyncio.sleep(0.05)

        assert route.call_count == 1

    async def test_disabled_chat_silences_commands_but_not_the_toggle(
        self, client: httpx.AsyncClient, router: respx.MockRouter
    ) -> None:
        """停用之后仍须能重新启用，否则会话将无法恢复。"""

        route = router.post(SEND_GROUP_URL).mock(return_value=httpx.Response(200, json={}))

        await post_event(client, group_message_payload(".bot off", event_id="E1"))
        await wait_until(lambda: route.call_count == 1)

        await post_event(client, group_message_payload(".ping", event_id="E2"))
        await asyncio.sleep(0.05)

        assert route.call_count == 1

        await post_event(client, group_message_payload(".bot on", event_id="E3"))
        await wait_until(lambda: route.call_count == 2)

        await post_event(client, group_message_payload(".ping", event_id="E4"))
        await wait_until(lambda: route.call_count == 3)

    async def test_disabling_a_plugin_silences_only_that_plugin(
        self, client: httpx.AsyncClient, router: respx.MockRouter
    ) -> None:
        """三层开关中的插件级开关：停用 dice 之后 .r 不再响应，.ra 不受影响。"""

        route = router.post(SEND_GROUP_URL).mock(return_value=httpx.Response(200, json={}))

        await post_event(client, group_message_payload(".plugin off dice", event_id="P1"))
        await wait_until(lambda: route.call_count == 1)

        await post_event(client, group_message_payload(".r 1d100", event_id="P2"))
        await asyncio.sleep(0.05)
        assert route.call_count == 1

        await post_event(client, group_message_payload(".ra 60", event_id="P3"))
        await wait_until(lambda: route.call_count == 2)

        await post_event(client, group_message_payload(".plugin on dice", event_id="P4"))
        await wait_until(lambda: route.call_count == 3)

        await post_event(client, group_message_payload(".r 1d100", event_id="P5"))
        await wait_until(lambda: route.call_count == 4)

    async def test_plugin_settings_persist_across_events(
        self, client: httpx.AsyncClient, router: respx.MockRouter
    ) -> None:
        """设置经 save_chat_settings 落到 JSON，下一条事件须读到新值。"""

        route = router.post(SEND_GROUP_URL).mock(return_value=httpx.Response(200, json={}))

        await post_event(client, group_message_payload(".set 20", event_id="S1"))
        await wait_until(lambda: route.call_count == 1)

        await post_event(client, group_message_payload(".r", event_id="S2"))
        await wait_until(lambda: route.call_count == 2)

        assert "D20=" in json.loads(route.calls.last.request.read())["content"]

    async def test_being_added_to_a_group_triggers_a_passive_reply(
        self, client: httpx.AsyncClient, router: respx.MockRouter
    ) -> None:
        """事件回复走 event_id，因此不消耗主动消息配额。"""

        route = router.post(SEND_GROUP_URL).mock(return_value=httpx.Response(200, json={}))

        await post_event(
            client,
            {
                "op": 0,
                "id": "EVENT_JOIN",
                "t": "GROUP_ADD_ROBOT",
                "d": {"group_openid": "G1", "op_member_openid": "U1"},
            },
        )
        await wait_until(lambda: route.call_count == 1)

        body = json.loads(route.calls.last.request.read())
        assert body["event_id"] == "EVENT_JOIN"
        assert "msg_id" not in body
        assert body["msg_seq"] == 1
        assert "DiceRobot" in body["content"]

    async def test_event_without_handlers_sends_nothing(
        self, client: httpx.AsyncClient, router: respx.MockRouter
    ) -> None:
        route = router.post(SEND_GROUP_URL).mock(return_value=httpx.Response(200, json={}))

        await post_event(
            client,
            {"op": 0, "id": "EVENT_LEAVE", "t": "GROUP_DEL_ROBOT", "d": {"group_openid": "G1"}},
        )
        await asyncio.sleep(0.05)

        assert route.call_count == 0

    async def test_non_command_message_is_ignored(self, client: httpx.AsyncClient, router: respx.MockRouter) -> None:
        """群开启全量消息推送时，闲聊须在触及下游之前丢弃。"""

        route = router.post(SEND_GROUP_URL).mock(return_value=httpx.Response(200, json={}))

        await post_event(client, group_message_payload("今天天气不错"))
        await asyncio.sleep(0.05)

        assert route.call_count == 0

    async def test_unknown_command_is_ignored(self, client: httpx.AsyncClient, router: respx.MockRouter) -> None:
        route = router.post(SEND_GROUP_URL).mock(return_value=httpx.Response(200, json={}))

        await post_event(client, group_message_payload(".不存在的指令"))
        await asyncio.sleep(0.05)

        assert route.call_count == 0


class TestHealth:
    async def test_reports_ok(self, client: httpx.AsyncClient) -> None:
        response = await client.get("/health")

        assert response.status_code == httpx.codes.OK
        assert response.json() == {"status": "ok"}
