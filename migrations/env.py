"""Alembic 运行环境。

数据库地址取自环境变量而非 ``alembic.ini``，以免同一份连接串出现在两处。此处刻意
不构造完整的 :class:`~dicerobot.config.Settings`，迁移不应因缺少机器人凭据而无法运行。
"""

from __future__ import annotations

import asyncio
import os
from logging.config import fileConfig

from alembic import context
from sqlalchemy import Connection, pool
from sqlalchemy.ext.asyncio import async_engine_from_config

from dicerobot.config import DEFAULT_DATABASE_URL
from dicerobot.storage.models import Base

config = context.config

# 仅在通过命令行独立运行时按 alembic.ini 配置日志。应用启动时的自动迁移会把
# configure_logger 置为假：fileConfig 会重设 root handler 并禁用未在 ini 中列出的
# logger，从而让应用已经装好的日志转发失效，uvicorn 与 httpx 的日志会就此消失。
if config.config_file_name is not None and config.attributes.get("configure_logger", True):
    fileConfig(config.config_file_name)

# 程序化调用（应用启动时的自动迁移）会预先设置好地址，此时不再覆盖。
if not config.get_main_option("sqlalchemy.url", None):
    config.set_main_option("sqlalchemy.url", os.environ.get("DATABASE_URL", DEFAULT_DATABASE_URL))

target_metadata = Base.metadata


def do_run_migrations(connection: Connection) -> None:
    context.configure(
        connection=connection,
        target_metadata=target_metadata,
        # SQLite 不支持多数 ALTER TABLE 操作，需以重建表的方式迁移。
        render_as_batch=True,
    )

    with context.begin_transaction():
        context.run_migrations()


def run_migrations_offline() -> None:
    """离线模式：仅生成 SQL，不建立连接。"""

    context.configure(
        url=config.get_main_option("sqlalchemy.url"),
        target_metadata=target_metadata,
        literal_binds=True,
        dialect_opts={"paramstyle": "named"},
        render_as_batch=True,
    )

    with context.begin_transaction():
        context.run_migrations()


async def run_async_migrations() -> None:
    engine = async_engine_from_config(
        config.get_section(config.config_ini_section, {}),
        prefix="sqlalchemy.",
        poolclass=pool.NullPool,
    )

    async with engine.connect() as connection:
        await connection.run_sync(do_run_migrations)

    await engine.dispose()


def run_migrations_online() -> None:
    asyncio.run(run_async_migrations())


if context.is_offline_mode():
    run_migrations_offline()
else:
    run_migrations_online()
