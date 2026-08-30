"""Webhook 入口。

处理函数只做验签、解析与转交三件事。业务处理须异步进行：平台在超时未收到响应时
会重推同一事件。

事件汇聚点通过本地 ``Protocol`` 传入，因此 ``qq`` 包不依赖运行时层，装配由
``app`` 负责。
"""

from __future__ import annotations

from typing import Protocol

from fastapi import APIRouter, HTTPException, Request, status
from fastapi.responses import JSONResponse
from loguru import logger
from pydantic import ValidationError

from dicerobot.errors import SignatureError
from dicerobot.qq.enums import OpCode
from dicerobot.qq.schemas import Payload, ValidationData, ValidationResponse
from dicerobot.qq.signature import SIGNATURE_HEADER, TIMESTAMP_HEADER, sign_challenge, verify_signature

__all__ = ["EventSink", "create_webhook_router"]


class EventSink(Protocol):
    """事件汇聚点。实现方须保证 :meth:`submit` 不阻塞。"""

    def submit(self, payload: Payload) -> None: ...


def create_webhook_router(*, path: str, secret: str, sink: EventSink) -> APIRouter:
    """构造 webhook 路由。

    Args:
        path: 回调路径，需与平台配置一致。
        secret: AppSecret，用于验签与回调地址校验。
        sink: 事件汇聚点。
    """

    router = APIRouter()

    @router.post(path, include_in_schema=False)
    async def handle(request: Request) -> JSONResponse:
        # 验签的输入必须是原始字节，重新序列化会改变字节导致校验失败。
        raw = await request.body()

        try:
            verify_signature(
                secret,
                signature=request.headers.get(SIGNATURE_HEADER, ""),
                timestamp=request.headers.get(TIMESTAMP_HEADER, ""),
                body=raw,
            )
        except SignatureError as e:
            # 不向对端透露失败原因，请求方未必是平台本身。
            logger.warning("Webhook 验签失败：{}", e)
            raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="unauthorized") from e

        try:
            payload = Payload.model_validate_json(raw)
        except ValidationError as e:
            logger.warning("Webhook payload 无法解析：{}", e)
            raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="bad request") from e

        if payload.op == OpCode.CALLBACK_VALIDATION:
            return _validate_callback(secret, payload)

        if payload.op == OpCode.DISPATCH:
            sink.submit(payload)
        else:
            logger.debug("收到未处理的操作码 {}", payload.op)

        return JSONResponse({})

    return router


def _validate_callback(secret: str, payload: Payload) -> JSONResponse:
    """响应回调地址校验（``op = 13``）。"""

    try:
        data = ValidationData.model_validate(payload.d)
    except ValidationError as e:
        logger.warning("回调地址校验的数据无法解析：{}", e)
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="bad request") from e

    response = ValidationResponse(
        plain_token=data.plain_token,
        signature=sign_challenge(secret, plain_token=data.plain_token, event_ts=data.event_ts),
    )

    logger.info("已响应回调地址校验")

    return JSONResponse(response.model_dump())
