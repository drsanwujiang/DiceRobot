"""access token 缓存与刷新的测试。

有效期由注入的假时钟控制，测试无需真实等待，亦不受机器负载影响。自动刷新的检查间隔另
行调至毫秒级：那是 ``asyncio.sleep`` 的真实等待，不受假时钟控制。
"""

from __future__ import annotations

import asyncio
import time
from collections.abc import AsyncIterator, Callable, Iterator
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


async def wait_until(predicate: Callable[[], bool], *, timeout: float = 2.0) -> None:
    deadline = time.monotonic() + timeout

    while time.monotonic() < deadline:
        if predicate():
            return

        await asyncio.sleep(0.005)

    raise AssertionError("等待条件成立超时")


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
    # 检查间隔调至毫秒级，使刷新在测试内即时发生；未调用 start 时该值无影响。
    return AccessTokenProvider(
        app_id="102",
        secret="secret",
        client=client,
        poll_interval=timedelta(milliseconds=5),
        now=clock,
    )


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

        # 仍在有效期内且未进入提前量窗口，不应刷新。
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

        # 一次失败后仍应能正常刷新。
        assert await provider.get() == "token-1"
        assert route.call_count == 2


class TestBackgroundRefresh:
    async def test_start_prefetches_the_token(self, provider: AccessTokenProvider, router: respx.MockRouter) -> None:
        """预取的意义在于把这次获取移出消息发送路径，一次往返接近一秒。"""

        route = router.post(ACCESS_TOKEN_URL).mock(return_value=token_response())

        await provider.start()

        try:
            assert route.call_count == 1
            assert await provider.get() == "token-1"
            # 已在启动时取到，取用不再产生请求。
            assert route.call_count == 1
        finally:
            await provider.stop()

    async def test_start_survives_a_failed_prefetch(
        self, provider: AccessTokenProvider, router: respx.MockRouter
    ) -> None:
        """预取失败不得阻断启动：验签与回调地址校验都不需要 token。"""

        router.post(ACCESS_TOKEN_URL).mock(return_value=httpx.Response(500))

        await provider.start()
        await provider.stop()

    async def test_refreshes_without_a_caller(
        self, provider: AccessTokenProvider, router: respx.MockRouter, clock: FakeClock
    ) -> None:
        route = router.post(ACCESS_TOKEN_URL).mock(side_effect=[token_response("token-1"), token_response("token-2")])

        await provider.start()

        try:
            # 越过 60 秒提前量，自动刷新应自行完成，无需任何调用方。
            clock.advance(7200 - 30)
            await wait_until(lambda: route.call_count == 2)

            assert await provider.get() == "token-2"
        finally:
            await provider.stop()

    async def test_checking_alone_does_not_call_the_api(
        self, provider: AccessTokenProvider, router: respx.MockRouter, clock: FakeClock
    ) -> None:
        """检查只比对本地记录的有效期，未落入窗口就不应产生请求。"""

        route = router.post(ACCESS_TOKEN_URL).mock(return_value=token_response())

        await provider.start()

        try:
            clock.advance(60)
            # 以检查间隔的数十倍等待，足够后台任务完成多轮检查。
            await asyncio.sleep(0.1)

            assert route.call_count == 1
        finally:
            await provider.stop()

    async def test_refresh_failure_keeps_the_task_alive(
        self, provider: AccessTokenProvider, router: respx.MockRouter, clock: FakeClock
    ) -> None:
        """刷新失败只是本轮失败，任务须存活到下一轮，否则此后不再有自动刷新。"""

        route = router.post(ACCESS_TOKEN_URL).mock(
            side_effect=[token_response("token-1"), httpx.Response(500), token_response("token-2")]
        )

        await provider.start()

        try:
            clock.advance(7200 - 30)
            await wait_until(lambda: route.call_count == 3)

            assert await provider.get() == "token-2"
        finally:
            await provider.stop()

    async def test_double_start_is_rejected(self, provider: AccessTokenProvider, router: respx.MockRouter) -> None:
        router.post(ACCESS_TOKEN_URL).mock(return_value=token_response())

        await provider.start()

        try:
            with pytest.raises(RuntimeError):
                await provider.start()
        finally:
            await provider.stop()

    async def test_stop_without_start_is_a_no_op(self, provider: AccessTokenProvider) -> None:
        await provider.stop()
