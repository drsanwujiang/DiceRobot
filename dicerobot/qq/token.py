"""App access token 的获取与缓存。

token 有效期 7200 秒，采用惰性刷新：取用时检查有效期，失效则就地续期。平台仅在接近
过期的 60 秒内才签发新 token，提前刷新只会拿回同一个，故不另设定时任务。
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

# 提前量，避免请求在途中 token 失效。
_REFRESH_MARGIN = timedelta(seconds=60)


class _AccessTokenResponse(BaseModel):
    """换取 token 接口的响应。``expires_in`` 平台返回字符串，由 pydantic 转换。"""

    access_token: str = Field(min_length=1)
    expires_in: int = Field(gt=0)


class AccessTokenProvider:
    """按需换取并缓存 access token。

    并发安全：多个协程同时发现 token 失效时，仅一个发起请求，其余等待其结果。
    """

    def __init__(
        self,
        *,
        app_id: str,
        secret: str,
        client: httpx.AsyncClient,
        now: Callable[[], datetime] = lambda: datetime.now(UTC),
    ) -> None:
        """
        Args:
            app_id: AppID。
            secret: AppSecret。
            client: 共享的 HTTP 客户端，生命周期由调用方管理。
            now: 取当前时间的可调用对象，供测试注入假时钟。
        """

        self._app_id = app_id
        self._secret = secret
        self._client = client
        self._now = now

        self._lock = asyncio.Lock()
        self._token: str | None = None
        self._expires_at = datetime.min.replace(tzinfo=UTC)

    async def get(self) -> str:
        """取一个当前有效的 token，必要时自动续期。

        Raises:
            TokenError: 换取失败或响应无法解析。
        """

        if self._valid():
            return self._token  # type: ignore[return-value]  # _valid 已保证非 None

        async with self._lock:
            # 等待锁期间可能已被其他协程续期。
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
