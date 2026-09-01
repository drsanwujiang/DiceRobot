"""数据库连接与会话管理。"""

from __future__ import annotations

from collections.abc import AsyncIterator
from contextlib import asynccontextmanager

from sqlalchemy import event
from sqlalchemy.engine.interfaces import DBAPIConnection
from sqlalchemy.ext.asyncio import AsyncEngine, AsyncSession, async_sessionmaker, create_async_engine
from sqlalchemy.pool import ConnectionPoolEntry

__all__ = ["Database"]

_BUSY_TIMEOUT_MS = 5000
"""写锁被占用时的等待上限，与驱动的默认值一致，此处显式声明。"""


class Database:
    """持有引擎与会话工厂，生命周期与应用一致。"""

    def __init__(self, url: str, *, echo: bool = False) -> None:
        self._engine: AsyncEngine = create_async_engine(url, echo=echo)
        self._sessionmaker = async_sessionmaker(self._engine, expire_on_commit=False)

        if self._engine.dialect.name == "sqlite":
            event.listen(self._engine.sync_engine, "connect", _apply_sqlite_pragmas)

    @property
    def engine(self) -> AsyncEngine:
        return self._engine

    @asynccontextmanager
    async def session(self) -> AsyncIterator[AsyncSession]:
        """开启一个会话，正常结束时提交，异常时回滚。"""

        async with self._sessionmaker() as session:
            try:
                yield session
                await session.commit()
            except Exception:
                await session.rollback()
                raise

    async def dispose(self) -> None:
        await self._engine.dispose()


def _apply_sqlite_pragmas(connection: DBAPIConnection, _: ConnectionPoolEntry) -> None:
    """每条连接建立时设置 PRAGMA。

    默认的回滚日志下，一个 worker 的读事务会挡住其他 worker 的提交；WAL 使读写互不阻塞。
    journal_mode 记在数据库文件中，重复设置无副作用。
    """

    cursor = connection.cursor()

    try:
        cursor.execute("PRAGMA journal_mode=WAL")
        cursor.execute(f"PRAGMA busy_timeout={_BUSY_TIMEOUT_MS}")
    finally:
        cursor.close()
