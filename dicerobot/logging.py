"""日志配置。

统一由 loguru 输出，并接管标准库 ``logging``，避免 uvicorn、SQLAlchemy 等库
各自安装 handler 导致格式不一致或重复输出。
"""

from __future__ import annotations

import inspect
import logging
import sys
from types import FrameType

from loguru import logger

from dicerobot.config import LogSettings

__all__ = ["InterceptHandler", "setup_logging"]

# 这些库默认自行安装 handler，需清空后交给 loguru。
_INTERCEPTED_LOGGERS = (
    "uvicorn",
    "uvicorn.error",
    "uvicorn.access",
    "fastapi",
    "httpx",
    "httpcore",
    "sqlalchemy.engine",
    "alembic",
)

_CONSOLE_FORMAT = (
    "<green>{time:YYYY-MM-DD HH:mm:ss.SSS}</green> | "
    "<level>{level: <8}</level> | "
    "<cyan>{name}</cyan>:<cyan>{function}</cyan>:<cyan>{line}</cyan> - "
    "<level>{message}</level>"
)


class InterceptHandler(logging.Handler):
    """把标准库 ``logging`` 的记录转发给 loguru。"""

    def emit(self, record: logging.LogRecord) -> None:
        try:
            level: str | int = logger.level(record.levelname).name
        except ValueError:
            level = record.levelno

        # 回溯到实际调用点，否则日志来源会统一显示为本文件。
        frame: FrameType | None = inspect.currentframe()
        depth = 0

        while frame and (depth == 0 or frame.f_code.co_filename == logging.__file__):
            frame = frame.f_back
            depth += 1

        logger.opt(depth=depth, exception=record.exc_info).log(level, record.getMessage())


def setup_logging(settings: LogSettings) -> None:
    """初始化日志，应在应用启动早期调用一次。"""

    logger.remove()
    logger.add(
        sys.stderr,
        level=settings.level,
        format=_CONSOLE_FORMAT,
        backtrace=True,
        # 关闭变量快照，异常回溯中可能包含 AppSecret 等敏感值。
        diagnose=False,
    )

    settings.directory.mkdir(parents=True, exist_ok=True)
    logger.add(
        settings.directory / "dicerobot_{time:YYYY-MM-DD}.log",
        level=settings.level,
        rotation=settings.rotation,
        retention=settings.retention,
        serialize=settings.serialize,
        encoding="utf-8",
        # 多 worker 并发写入时保证单条日志不被截断。
        enqueue=True,
        backtrace=True,
        diagnose=False,
    )

    logging.basicConfig(handlers=[InterceptHandler()], level=logging.NOTSET, force=True)

    for name in _INTERCEPTED_LOGGERS:
        std_logger = logging.getLogger(name)
        std_logger.handlers = [InterceptHandler()]
        std_logger.propagate = False
