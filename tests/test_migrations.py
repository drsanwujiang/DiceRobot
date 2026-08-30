"""启动时自动迁移的测试。"""

from __future__ import annotations

import logging
from pathlib import Path

import pytest
from sqlalchemy import text
from sqlalchemy.ext.asyncio import create_async_engine

from dicerobot.config import LogSettings
from dicerobot.errors import ConfigurationError
from dicerobot.logging import InterceptHandler, setup_logging
from dicerobot.storage import upgrade_to_head

# 这些库的日志由应用统一转发，迁移不得影响它们。
_FORWARDED_LOGGERS = ("uvicorn.access", "uvicorn.error", "httpx")


async def table_names(url: str) -> set[str]:
    engine = create_async_engine(url)

    try:
        async with engine.connect() as connection:
            result = await connection.execute(text("SELECT name FROM sqlite_master WHERE type = 'table'"))

            return {row[0] for row in result}
    finally:
        await engine.dispose()


class TestUpgrade:
    async def test_creates_every_table_on_an_empty_database(self, tmp_path: Path) -> None:
        url = f"sqlite+aiosqlite:///{tmp_path.as_posix()}/fresh.db"

        await upgrade_to_head(url)

        assert {"chats", "members", "plugin_states", "chat_plugin_states"} <= await table_names(url)

    async def test_is_idempotent(self, tmp_path: Path) -> None:
        """容器重启会再次执行迁移，重复运行必须无副作用。"""

        url = f"sqlite+aiosqlite:///{tmp_path.as_posix()}/fresh.db"

        await upgrade_to_head(url)
        await upgrade_to_head(url)

        assert "chats" in await table_names(url)

    async def test_missing_config_is_reported(self, tmp_path: Path) -> None:
        with pytest.raises(ConfigurationError, match=r"alembic\.ini"):
            await upgrade_to_head("sqlite+aiosqlite:///:memory:", root=tmp_path)


class TestLoggingIsPreserved:
    """alembic 的 fileConfig 会重设 root handler 并禁用未列出的 logger。

    程序化调用时必须绕开它，否则应用启动后 uvicorn 与 httpx 的日志会静默消失。
    """

    async def test_forwarded_loggers_stay_enabled(self, tmp_path: Path) -> None:
        setup_logging(LogSettings(level="WARNING", directory=tmp_path / "logs"))

        await upgrade_to_head(f"sqlite+aiosqlite:///{tmp_path.as_posix()}/fresh.db")

        for name in _FORWARDED_LOGGERS:
            assert logging.getLogger(name).disabled is False, name

    async def test_root_handler_is_not_replaced(self, tmp_path: Path) -> None:
        setup_logging(LogSettings(level="WARNING", directory=tmp_path / "logs"))

        await upgrade_to_head(f"sqlite+aiosqlite:///{tmp_path.as_posix()}/fresh.db")

        assert any(isinstance(handler, InterceptHandler) for handler in logging.getLogger().handlers)
