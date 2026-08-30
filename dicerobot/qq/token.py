"""App access token 的获取与缓存。

token 有效期 7200 秒。平台仅在过期前 60 秒内签发新的，提前刷新返回的仍是同一个，因此
刷新时机没有提前的余地，只能落在这 60 秒内。

获取一次接近一秒，落在消息发送路径上代价明显，故启动时预取，并由后台任务周期性检查
剩余有效期。检查只比对本地记录，不产生请求；只有落入提前量窗口才会真正调用接口。

取用时的惰性刷新保留为后备路径：平台可能提前吊销 token，自动刷新预知不到，而收到 401
之后 :meth:`AccessTokenProvider.invalidate` 会使下一次取用立即重新获取。
"""

from __future__ import annotations

import asyncio
from collections.abc import Callable
from datetime import UTC, datetime, timedelta

import httpx
from loguru import logger
from pydantic import BaseModel, Field

from dicerobot.errors import TokenError
from dicerobot.qq import API_BASE_URL

__all__ = ["ACCESS_TOKEN_URL", "AccessTokenProvider"]

ACCESS_TOKEN_URL = f"{API_BASE_URL}/app/getAppAccessToken"

# 提前量，避免请求在途中 token 失效。与平台签发新 token 的窗口同宽：早于此刷新，
# 返回的仍是同一个。
_REFRESH_MARGIN = timedelta(seconds=60)

# 自动刷新的检查间隔。取值需明显小于提前量，以保证窗口内至少检查两次。
_POLL_INTERVAL = timedelta(seconds=30)


class _AccessTokenResponse(BaseModel):
    """获取 token 接口的响应。``expires_in`` 平台返回字符串，由 pydantic 转换。"""

    access_token: str = Field(min_length=1)
    expires_in: int = Field(gt=0)


class AccessTokenProvider:
    """按需获取并缓存 access token。

    并发安全：多个协程同时发现 token 失效时，仅一个发起请求，其余等待其结果。
    """

    def __init__(
        self,
        *,
        app_id: str,
        secret: str,
        client: httpx.AsyncClient,
        poll_interval: timedelta = _POLL_INTERVAL,
        now: Callable[[], datetime] = lambda: datetime.now(UTC),
    ) -> None:
        """
        Args:
            app_id: AppID。
            secret: AppSecret。
            client: 共享的 HTTP 客户端，生命周期由调用方管理。
            poll_interval: 自动刷新检查剩余有效期的间隔。
            now: 取当前时间的可调用对象，供测试注入假时钟。
        """

        self._app_id = app_id
        self._secret = secret
        self._client = client
        self._poll_interval = poll_interval
        self._now = now

        self._lock = asyncio.Lock()
        self._token: str | None = None
        self._expires_at = datetime.min.replace(tzinfo=UTC)
        self._task: asyncio.Task[None] | None = None

    async def start(self) -> None:
        """预取 token 并启动自动刷新。

        预取失败只记录错误，不阻断启动：验签与回调地址校验都不需要 token，发送消息
        前的惰性刷新也会重试，不应因平台的一次抖动导致进程无法启动。

        Raises:
            RuntimeError: 重复启动。
        """

        if self._task is not None:
            raise RuntimeError("自动刷新已经启动")

        try:
            await self.get()
        except TokenError as e:
            logger.error("预取 access token 失败，将在首次发送消息时重试：{}", e)

        self._task = asyncio.create_task(self._run(), name="token-refresher")

        logger.debug("access token 自动刷新已启动，检查间隔 {} 秒", self._poll_interval.total_seconds())

    async def stop(self) -> None:
        """停止自动刷新。未启动时为空操作。"""

        if self._task is None:
            return

        self._task.cancel()
        await asyncio.gather(self._task, return_exceptions=True)
        self._task = None

        logger.debug("access token 自动刷新已停止")

    async def get(self) -> str:
        """取一个当前有效的 token，必要时自动刷新。

        Raises:
            TokenError: 获取失败或响应无法解析。
        """

        if self._valid():
            return self._token  # type: ignore[return-value]  # _valid 已保证非 None

        async with self._lock:
            # 等待锁期间可能已被其他协程刷新。
            if self._valid():
                return self._token  # type: ignore[return-value]

            await self._refresh()

        return self._token  # type: ignore[return-value]

    def invalidate(self) -> None:
        """作废当前缓存的 token。

        供调用方在收到 401 时使用：平台可能提前吊销 token，此时本地记录的有效期
        不再可信。
        """

        self._token = None
        self._expires_at = datetime.min.replace(tzinfo=UTC)

        logger.debug("access token 缓存已作废")

    async def _run(self) -> None:
        """周期性检查剩余有效期，落入提前量窗口时刷新。

        检查本身不产生请求：token 仍然有效时 :meth:`get` 直接返回缓存。
        """

        while True:
            await asyncio.sleep(self._poll_interval.total_seconds())

            try:
                await self.get()
            except TokenError as e:
                # 任务须存活至关停：本次失败由下次检查与取用时的惰性刷新覆盖。
                logger.warning("自动刷新 access token 失败：{}", e)
            except Exception:
                logger.exception("自动刷新 access token 时发生未捕获的异常")

    def _valid(self) -> bool:
        return self._token is not None and self._now() + _REFRESH_MARGIN < self._expires_at

    async def _refresh(self) -> None:
        logger.debug("正在获取 access token")

        try:
            response = await self._client.post(
                ACCESS_TOKEN_URL,
                json={"appId": self._app_id, "clientSecret": self._secret},
            )
            response.raise_for_status()
            payload = _AccessTokenResponse.model_validate(response.json())
        except httpx.HTTPError as e:
            raise TokenError(f"获取 access token 失败：{e}") from e
        except ValueError as e:
            # 含 JSON 解析失败与 pydantic 的 ValidationError。
            # 响应体不写入日志，避免 AppSecret 或 token 落盘。
            raise TokenError("获取 access token 的响应无法解析，请检查 AppID 与 AppSecret") from e

        self._token = payload.access_token
        self._expires_at = self._now() + timedelta(seconds=payload.expires_in)

        logger.info("access token 已更新，{} 秒后过期", payload.expires_in)
