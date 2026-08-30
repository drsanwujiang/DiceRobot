"""应用装配。

各组件在此创建并连接：``qq`` 提供平台能力，``bot`` 提供运行时，两者不直接依赖对方的
构造过程。
"""

from __future__ import annotations

from collections.abc import AsyncIterator
from contextlib import asynccontextmanager

import httpx
from fastapi import FastAPI
from loguru import logger

from dicerobot.bot.loader import load_registry
from dicerobot.bot.pipeline import Pipeline
from dicerobot.config import Settings, get_settings
from dicerobot.logging import setup_logging
from dicerobot.qq.client import QQClient
from dicerobot.qq.token import AccessTokenProvider
from dicerobot.qq.webhook import create_webhook_router
from dicerobot.storage import Database, upgrade_to_head

__all__ = ["create_app"]


def create_app(settings: Settings | None = None) -> FastAPI:
    """构造应用实例。

    Args:
        settings: 配置。省略时从环境变量读取，测试可传入以覆盖。
    """

    settings = settings if settings is not None else get_settings()
    setup_logging(settings.log)

    secret = settings.qq.secret.get_secret_value()

    # 所有出站请求共用一个连接池，其生命周期与应用一致，在 lifespan 中关闭。
    http_client = httpx.AsyncClient(timeout=settings.qq.request_timeout)
    token_provider = AccessTokenProvider(app_id=settings.qq.app_id, secret=secret, client=http_client)
    client = QQClient(app_id=settings.qq.app_id, token_provider=token_provider, client=http_client)

    database = Database(settings.database_url, echo=settings.debug)
    registry = load_registry()
    pipeline = Pipeline(
        registry=registry,
        client=client,
        database=database,
        settings=settings.bot,
        debug=settings.debug,
    )

    @asynccontextmanager
    async def lifespan(_: FastAPI) -> AsyncIterator[None]:
        logger.info("DiceRobot 启动，已加载 {} 个插件", len(registry.plugins))
        await upgrade_to_head(settings.database_url)
        await pipeline.start()

        try:
            yield
        finally:
            await pipeline.stop()
            await http_client.aclose()
            await database.dispose()
            logger.info("DiceRobot 已停止")

    app = FastAPI(
        title="DiceRobot",
        lifespan=lifespan,
        # 仅对外暴露 webhook，无需交互式文档。
        docs_url=None,
        redoc_url=None,
        openapi_url=None,
    )
    app.include_router(create_webhook_router(path=settings.webhook_path, secret=secret, sink=pipeline))

    @app.get("/health", include_in_schema=False)
    async def health() -> dict[str, str]:
        return {"status": "ok"}

    return app
