"""QQ 开放平台 OpenAPI 客户端。

仅封装本项目用到的接口。鉴权头由客户端统一注入，调用方无需关心 token 的获取与续期。
"""

from __future__ import annotations

from typing import Any

import httpx
from loguru import logger

from dicerobot.errors import ApiError
from dicerobot.qq.enums import MessageType
from dicerobot.qq.schemas import SendMessageResult
from dicerobot.qq.token import AccessTokenProvider

__all__ = ["QQClient"]


class QQClient:
    """调用平台 OpenAPI。"""

    def __init__(
        self,
        *,
        app_id: str,
        base_url: str,
        token_provider: AccessTokenProvider,
        client: httpx.AsyncClient,
    ) -> None:
        """
        Args:
            app_id: AppID，同时作为 ``X-Union-Appid`` 请求头。
            base_url: OpenAPI 域名，沙箱与正式环境不同。
            token_provider: access token 来源。
            client: 共享的 HTTP 客户端，生命周期由调用方管理。
        """

        self._app_id = app_id
        self._base_url = base_url.rstrip("/")
        self._token_provider = token_provider
        self._client = client

    async def send_group_message(
        self,
        *,
        group_openid: str,
        content: str,
        msg_seq: int,
        msg_id: str | None = None,
        event_id: str | None = None,
    ) -> SendMessageResult:
        """向群发送被动回复。

        Args:
            group_openid: 目标群标识。
            content: 纯文本内容。
            msg_seq: 回复序号，自 1 起递增。相同的来源与序号组合会被平台拒绝。
            msg_id: 所回复消息的 ID。
            event_id: 所回复事件的 ID，用于消息之外的事件。

        ``msg_id`` 与 ``event_id`` 须提供其一，两者都省略即成为主动消息，配额极其
        有限，本项目不使用。
        """

        return await self._send(
            f"/v2/groups/{group_openid}/messages",
            content=content,
            msg_seq=msg_seq,
            msg_id=msg_id,
            event_id=event_id,
        )

    async def send_c2c_message(
        self,
        *,
        openid: str,
        content: str,
        msg_seq: int,
        msg_id: str | None = None,
        event_id: str | None = None,
    ) -> SendMessageResult:
        """向单聊发送被动回复。参数含义同 :meth:`send_group_message`。"""

        return await self._send(
            f"/v2/users/{openid}/messages",
            content=content,
            msg_seq=msg_seq,
            msg_id=msg_id,
            event_id=event_id,
        )

    async def _send(
        self,
        path: str,
        *,
        content: str,
        msg_seq: int,
        msg_id: str | None,
        event_id: str | None,
    ) -> SendMessageResult:
        payload: dict[str, Any] = {
            "msg_type": MessageType.TEXT.value,
            "content": content,
            "msg_seq": msg_seq,
        }

        # 未提供的来源标识不能以 null 出现在请求体中，平台会按主动消息处理。
        if msg_id is not None:
            payload["msg_id"] = msg_id

        if event_id is not None:
            payload["event_id"] = event_id

        return SendMessageResult.model_validate(await self._request("POST", path, payload))

    async def _request(self, method: str, path: str, payload: dict[str, Any]) -> dict[str, Any]:
        """发起一次带鉴权的请求。

        收到 401 时作废本地 token 并重试一次，以应对平台提前吊销 token 的情况。
        仅重试一次，避免凭据确实无效时陷入循环。
        """

        response = await self._attempt(method, path, payload)

        if response.status_code == httpx.codes.UNAUTHORIZED:
            logger.warning("access token 被平台拒绝，作废后重试一次")
            self._token_provider.invalidate()
            response = await self._attempt(method, path, payload)

        if response.is_success:
            return self._parse_body(response)

        raise self._to_api_error(response)

    async def _attempt(self, method: str, path: str, payload: dict[str, Any]) -> httpx.Response:
        """发起单次请求，不做重试。"""

        token = await self._token_provider.get()

        try:
            return await self._client.request(
                method,
                f"{self._base_url}{path}",
                json=payload,
                headers={
                    "Authorization": f"QQBot {token}",
                    "X-Union-Appid": self._app_id,
                },
            )
        except httpx.HTTPError as e:
            raise ApiError(code=-1, message=f"请求失败：{e}", status_code=0) from e

    @staticmethod
    def _parse_body(response: httpx.Response) -> dict[str, Any]:
        if not response.content:
            return {}

        try:
            body = response.json()
        except ValueError:
            return {}

        return body if isinstance(body, dict) else {}

    @classmethod
    def _to_api_error(cls, response: httpx.Response) -> ApiError:
        body = cls._parse_body(response)
        # 平台在不同接口上使用过 code/message 与 errcode/errmsg 两套字段名。
        code = body.get("code", body.get("errcode", -1))
        message = body.get("message", body.get("errmsg", response.text[:200]))

        return ApiError(
            code=int(code) if isinstance(code, int | str) and str(code).lstrip("-").isdigit() else -1,
            message=str(message),
            status_code=response.status_code,
        )
