"""QQ 开放平台适配。"""

from __future__ import annotations

from typing import Protocol

from dicerobot.qq.schemas import Payload

__all__ = ["API_BASE_URL", "EventSink"]

# 平台接口域名。V2 已将调用域名统一至此：获取 access token 与调用 OpenAPI 共用，
# 且不再区分环境。
API_BASE_URL = "https://api.bot.qq.com"


class EventSink(Protocol):
    """事件接收方。

    webhook 与 WebSocket 网关两种接入方式共用，故声明在包内而非其中一方：下游对事件
    由哪一方送达并不知情。实现方须保证 :meth:`submit` 不阻塞——webhook 的响应超时会
    触发平台重推，网关的接收循环阻塞则会拖住心跳。
    """

    def submit(self, payload: Payload) -> None: ...
