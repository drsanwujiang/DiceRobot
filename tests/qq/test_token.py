"""access token 缓存与续期的测试。

时间由注入的假时钟控制，测试无需真实等待，亦不受机器负载影响。
"""

from __future__ import annotations

import asyncio
from collections.abc import AsyncIterator, Iterator
from datetime import UTC, datetime, timedelta

import httpx
import pytest
import respx

from dicerobot.errors import TokenError
from dicerobot.qq.token import ACCESS_TOKEN_URL, AccessTokenProvider


class FakeClock:
    """可手动推进的假时钟。"""

    def __init__(self) -> None:
        self.now = datetime(2026, 1, 1, tzinfo=UTC)

    def __call__(self) -> datetime:
        return self.now

    def advance(self, seconds: float) -> None:
        self.now += timedelta(seconds=seconds)


def token_response(token: str = "token-1", expires_in: str = "7200") -> httpx.Response:
    # 平台的 expires_in 返回字符串，此处保持一致。
    return httpx.Response(200, json={"access_token": token, "expires_in": expires_in})


@pytest.fixture
def clock() -> FakeClock:
    return FakeClock()


@pytest.fixture
def router() -> Iterator[respx.MockRouter]:
    with respx.mock(assert_all_called=False) as mock_router:
        yield mock_router


@pytest.fixture
async def client() -> AsyncIterator[httpx.AsyncClient]:
    async with httpx.AsyncClient() as http_client:
        yield http_client


@pytest.fixture
def provider(client: httpx.AsyncClient, clock: FakeClock) -> AccessTokenProvider:
    return AccessTokenProvider(app_id="102", secret="secret", client=client, now=clock)


class TestGet:
    async def test_fetches_token_on_first_use(self, provider: AccessTokenProvider, router: respx.MockRouter) -> None:
        route = router.post(ACCESS_TOKEN_URL).mock(return_value=token_response())

        assert await provider.get() == "token-1"
        assert route.call_count == 1
        assert route.calls.last.request.read() == b'{"appId":"102","clientSecret":"secret"}'

    async def test_reuses_cached_token(self, provider: AccessTokenProvider, router: respx.MockRouter) -> None:
        route = router.post(ACCESS_TOKEN_URL).mock(return_value=token_response())

        await provider.get()
        await provider.get()

        assert route.call_count == 1

    async def test_refreshes_before_expiry(
        self, provider: AccessTokenProvider, router: respx.MockRouter, clock: FakeClock
    ) -> None:
        route = router.post(ACCESS_TOKEN_URL).mock(side_effect=[token_response("token-1"), token_response("token-2")])

        assert await provider.get() == "token-1"

        # 仍在有效期内且未进入提前量窗口，不应续期。
        clock.advance(7200 - 61)
        assert await provider.get() == "token-1"
        assert route.call_count == 1

        # 越过 60 秒提前量后，应在过期前换新。
        clock.advance(2)
        assert await provider.get() == "token-2"
        assert route.call_count == 2

    async def test_concurrent_callers_trigger_a_single_request(
        self, provider: AccessTokenProvider, router: respx.MockRouter
    ) -> None:
        route = router.post(ACCESS_TOKEN_URL).mock(return_value=token_response())

        tokens = await asyncio.gather(*(provider.get() for _ in range(10)))

        assert tokens == ["token-1"] * 10
        assert route.call_count == 1


class TestInvalidate:
    async def test_forces_a_refetch(self, provider: AccessTokenProvider, router: respx.MockRouter) -> None:
        route = router.post(ACCESS_TOKEN_URL).mock(side_effect=[token_response("token-1"), token_response("token-2")])

        assert await provider.get() == "token-1"
        provider.invalidate()

        assert await provider.get() == "token-2"
        assert route.call_count == 2


class TestFailures:
    async def test_http_error_raises_token_error(self, provider: AccessTokenProvider, router: respx.MockRouter) -> None:
        router.post(ACCESS_TOKEN_URL).mock(return_value=httpx.Response(401, json={"message": "invalid appid"}))

        with pytest.raises(TokenError):
            await provider.get()

    async def test_connection_error_raises_token_error(
        self, provider: AccessTokenProvider, router: respx.MockRouter
    ) -> None:
        router.post(ACCESS_TOKEN_URL).mock(side_effect=httpx.ConnectError("boom"))

        with pytest.raises(TokenError):
            await provider.get()

    async def test_malformed_response_raises_token_error(
        self, provider: AccessTokenProvider, router: respx.MockRouter
    ) -> None:
        router.post(ACCESS_TOKEN_URL).mock(return_value=httpx.Response(200, json={"unexpected": "shape"}))

        with pytest.raises(TokenError):
            await provider.get()

    async def test_failure_does_not_poison_the_cache(
        self, provider: AccessTokenProvider, router: respx.MockRouter
    ) -> None:
        route = router.post(ACCESS_TOKEN_URL).mock(side_effect=[httpx.Response(500), token_response("token-1")])

        with pytest.raises(TokenError):
            await provider.get()

        # 一次失败后仍应能正常续期。
        assert await provider.get() == "token-1"
        assert route.call_count == 2
