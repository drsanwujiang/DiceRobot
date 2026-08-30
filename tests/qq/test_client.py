"""OpenAPI 客户端的测试。

覆盖被动回复的请求体拼装、401 后的单次重试，以及 V2 失败响应到 :class:`ApiError`
的映射——错误码字段名一旦对不上，所有平台错误都会退化成 -1 而无从排查。
"""

from __future__ import annotations

import json
from collections.abc import AsyncIterator, Iterator

import httpx
import pytest
import respx

from dicerobot.errors import ApiError
from dicerobot.qq import API_BASE_URL
from dicerobot.qq.client import QQClient
from dicerobot.qq.token import ACCESS_TOKEN_URL, AccessTokenProvider

APP_ID = "102"
SEND_GROUP_URL = f"{API_BASE_URL}/v2/groups/G1/messages"
SEND_C2C_URL = f"{API_BASE_URL}/v2/users/U1/messages"


@pytest.fixture
def router() -> Iterator[respx.MockRouter]:
    with respx.mock(assert_all_called=False) as mock_router:
        mock_router.post(ACCESS_TOKEN_URL).mock(
            return_value=httpx.Response(200, json={"access_token": "token-1", "expires_in": "7200"})
        )
        yield mock_router


@pytest.fixture
async def http_client() -> AsyncIterator[httpx.AsyncClient]:
    async with httpx.AsyncClient() as client:
        yield client


@pytest.fixture
def client(http_client: httpx.AsyncClient) -> QQClient:
    return QQClient(
        app_id=APP_ID,
        token_provider=AccessTokenProvider(app_id=APP_ID, secret="secret", client=http_client),
        client=http_client,
    )


class TestSend:
    async def test_group_reply_carries_credentials_and_sequence(
        self, client: QQClient, router: respx.MockRouter
    ) -> None:
        route = router.post(SEND_GROUP_URL).mock(return_value=httpx.Response(200, json={"id": "REPLY_1"}))

        result = await client.send_group_message(group_openid="G1", content="pong", msg_seq=1, msg_id="MSG_1")

        assert result.id == "REPLY_1"

        request = route.calls.last.request
        assert request.headers["Authorization"] == "QQBot token-1"
        assert json.loads(request.read()) == {"msg_type": 0, "content": "pong", "msg_seq": 1, "msg_id": "MSG_1"}

    async def test_c2c_reply_hits_the_user_endpoint(self, client: QQClient, router: respx.MockRouter) -> None:
        route = router.post(SEND_C2C_URL).mock(return_value=httpx.Response(200, json={}))

        await client.send_c2c_message(openid="U1", content="pong", msg_seq=1, msg_id="MSG_1")

        assert route.call_count == 1

    async def test_event_reply_omits_the_message_id(self, client: QQClient, router: respx.MockRouter) -> None:
        """未提供的来源标识不能以 null 出现，否则平台按主动消息处理并另计配额。"""

        route = router.post(SEND_GROUP_URL).mock(return_value=httpx.Response(200, json={}))

        await client.send_group_message(group_openid="G1", content="hi", msg_seq=1, event_id="EVENT_1")

        body = json.loads(route.calls.last.request.read())
        assert body["event_id"] == "EVENT_1"
        assert "msg_id" not in body

    async def test_empty_body_is_accepted(self, client: QQClient, router: respx.MockRouter) -> None:
        router.post(SEND_GROUP_URL).mock(return_value=httpx.Response(200))

        assert (await client.send_group_message(group_openid="G1", content="hi", msg_seq=1, msg_id="M")).id is None


class TestUnauthorized:
    async def test_retries_once_with_a_fresh_token(self, client: QQClient, router: respx.MockRouter) -> None:
        token_route = router.post(ACCESS_TOKEN_URL).mock(
            side_effect=[
                httpx.Response(200, json={"access_token": "stale", "expires_in": "7200"}),
                httpx.Response(200, json={"access_token": "fresh", "expires_in": "7200"}),
            ]
        )
        route = router.post(SEND_GROUP_URL).mock(
            side_effect=[httpx.Response(401, json={}), httpx.Response(200, json={"id": "REPLY_1"})]
        )

        await client.send_group_message(group_openid="G1", content="pong", msg_seq=1, msg_id="MSG_1")

        assert token_route.call_count == 2
        assert route.call_count == 2
        assert route.calls.last.request.headers["Authorization"] == "QQBot fresh"

    async def test_gives_up_after_the_retry(self, client: QQClient, router: respx.MockRouter) -> None:
        route = router.post(SEND_GROUP_URL).mock(return_value=httpx.Response(401, json={"err_code": 11243}))

        with pytest.raises(ApiError) as error:
            await client.send_group_message(group_openid="G1", content="pong", msg_seq=1, msg_id="MSG_1")

        assert route.call_count == 2
        assert error.value.code == 11243


class TestErrors:
    async def test_maps_the_v2_failure_body(self, client: QQClient, router: respx.MockRouter) -> None:
        router.post(SEND_GROUP_URL).mock(
            return_value=httpx.Response(
                400,
                json={"err_code": 40034005, "message": "回复消息msg_id已过期", "trace_id": "4a8a6156"},
            )
        )

        with pytest.raises(ApiError) as error:
            await client.send_group_message(group_openid="G1", content="pong", msg_seq=1, msg_id="MSG_1")

        assert error.value.code == 40034005
        assert error.value.message == "回复消息msg_id已过期"
        assert error.value.trace_id == "4a8a6156"
        assert error.value.status_code == httpx.codes.BAD_REQUEST
        assert "4a8a6156" in str(error.value)

    async def test_falls_back_to_the_legacy_code_field(self, client: QQClient, router: respx.MockRouter) -> None:
        router.post(SEND_GROUP_URL).mock(return_value=httpx.Response(400, json={"code": 11244, "message": "错误"}))

        with pytest.raises(ApiError) as error:
            await client.send_group_message(group_openid="G1", content="pong", msg_seq=1, msg_id="MSG_1")

        assert error.value.code == 11244
        assert error.value.trace_id is None

    async def test_unparsable_body_becomes_an_unknown_code(self, client: QQClient, router: respx.MockRouter) -> None:
        router.post(SEND_GROUP_URL).mock(return_value=httpx.Response(502, text="<html>bad gateway</html>"))

        with pytest.raises(ApiError) as error:
            await client.send_group_message(group_openid="G1", content="pong", msg_seq=1, msg_id="MSG_1")

        assert error.value.code == -1
        assert error.value.status_code == httpx.codes.BAD_GATEWAY

    async def test_network_failure_becomes_an_api_error(self, client: QQClient, router: respx.MockRouter) -> None:
        router.post(SEND_GROUP_URL).mock(side_effect=httpx.ConnectError("connection refused"))

        with pytest.raises(ApiError) as error:
            await client.send_group_message(group_openid="G1", content="pong", msg_seq=1, msg_id="MSG_1")

        assert error.value.status_code == 0
