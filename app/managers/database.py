from typing import TYPE_CHECKING
from contextlib import asynccontextmanager
from collections.abc import AsyncGenerator

from loguru import logger
from sqlalchemy.ext.asyncio import AsyncEngine, AsyncSession, create_async_engine, async_sessionmaker

from ..globals import DATABASE
from ..database import Base
from . import Manager

if TYPE_CHECKING:
    from ..context import AppContext

__all__ = [
    "DatabaseManager"
]


class DatabaseManager(Manager):
    def __init__(self, context: "AppContext") -> None:
        super().__init__(context)

        self.database_url = f"sqlite+aiosqlite:///{DATABASE}?_pragma=journal_mode=wal"
        self.engine: AsyncEngine | None = None
        self.session: async_sessionmaker[AsyncSession] | None = None

    async def initialize(self) -> None:
        self.engine = create_async_engine(self.database_url)
        self.session = async_sessionmaker(self.engine, expire_on_commit=False)

        # Create all tables
        async with self.engine.begin() as connection:
            await connection.run_sync(Base.metadata.create_all)

        logger.debug("Database manager initialized")

    async def cleanup(self) -> None:
        logger.debug("Clean database manager")

        if self.engine:
            await self.engine.dispose()

    @asynccontextmanager
    async def get_session(self) -> AsyncGenerator[AsyncSession]:
        async with self.session.begin() as session:
            yield session
