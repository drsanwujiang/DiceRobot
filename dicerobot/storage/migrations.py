"""在应用启动时应用数据库迁移。

单实例自托管场景下，把迁移并入启动流程比要求运维记得先执行一条命令更可靠。
"""

from __future__ import annotations

import asyncio
from pathlib import Path

from alembic import command
from alembic.config import Config
from loguru import logger

from dicerobot.errors import ConfigurationError

__all__ = ["upgrade_to_head"]

_CONFIG_FILENAME = "alembic.ini"


async def upgrade_to_head(database_url: str, *, root: Path | None = None) -> None:
    """把数据库升级到最新版本。

    Args:
        database_url: 目标数据库。以参数传入而非经由环境变量，避免修改进程全局状态。
        root: ``alembic.ini`` 所在目录，默认为当前工作目录。

    Raises:
        ConfigurationError: 找不到 ``alembic.ini``。
    """

    config_path = (root if root is not None else Path.cwd()) / _CONFIG_FILENAME

    if not config_path.is_file():
        raise ConfigurationError(f"找不到 {config_path}，请在项目根目录下启动")

    config = Config(str(config_path))
    config.set_main_option("script_location", str(config_path.parent / "migrations"))
    config.set_main_option("sqlalchemy.url", database_url)
    # 不让 alembic 按 alembic.ini 重设日志，否则会覆盖应用已装好的转发配置。
    config.attributes["configure_logger"] = False

    # alembic 的迁移入口是同步的，且内部会自行 asyncio.run，因此必须放到独立线程中，
    # 否则会与当前事件循环冲突。
    await asyncio.to_thread(command.upgrade, config, "head")

    logger.info("数据库已升级至最新版本")
