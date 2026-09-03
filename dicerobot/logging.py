"""日志配置。

统一由 loguru 输出，并接管标准库 ``logging``，避免 uvicorn、SQLAlchemy 等库
各自安装 handler 导致格式不一致或重复输出。

事件 ID 通过 ``logger.contextualize(event_id=...)`` 注入上下文，处理同一事件期间
产生的日志（含插件、平台调用与转发自标准库的日志）都会携带该字段，便于关联 webhook、
队列与执行各阶段的记录。上下文由 contextvar 承载，worker 之间互不影响。
"""

from __future__ import annotations

import inspect
import logging
import sys
from types import FrameType
from typing import TYPE_CHECKING

from loguru import logger

from dicerobot.config import LogSettings

if TYPE_CHECKING:
    # loguru 只在类型存根中定义 Record，运行时无法导入。
    from loguru import Record

__all__ = ["TRACE_BODY_LIMIT", "InterceptHandler", "preview", "setup_logging"]

# TRACE 报文在日志中的截断长度。报文可能很长，且含用户内容，不宜整条落盘。
TRACE_BODY_LIMIT = 2000

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

_FORMAT_PREFIX = "<green>{time:YYYY-MM-DD HH:mm:ss.SSS}</green> | <level>{level: <8}</level> | "
_FORMAT_EVENT_ID = "<magenta>{extra[event_id]}</magenta> | "
_FORMAT_SUFFIX = (
    "<cyan>{name}</cyan>:<cyan>{function}</cyan>:<cyan>{line}</cyan> - <level>{message}</level>\n{exception}"
)


def _format_record(record: Record) -> str:
    """按记录是否携带事件 ID 选择格式。

    未绑定事件 ID 的记录（启动、token 刷新等）不插入该列，否则每行都会多出一个占位符。
    ``format`` 为可调用对象时 loguru 不再自动追加换行与异常，故模板须显式包含。
    """

    if record["extra"].get("event_id"):
        return _FORMAT_PREFIX + _FORMAT_EVENT_ID + _FORMAT_SUFFIX

    return _FORMAT_PREFIX + _FORMAT_SUFFIX


def preview(text: str, limit: int = TRACE_BODY_LIMIT) -> str:
    """截断过长的报文，并标出原始长度。"""

    if len(text) <= limit:
        return text

    return f"{text[:limit]}…（共 {len(text)} 字符）"


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
        format=_format_record,
        backtrace=True,
        # 关闭变量快照，异常回溯中可能包含 AppSecret 等敏感值。
        diagnose=False,
    )

    settings.directory.mkdir(parents=True, exist_ok=True)
    logger.add(
        settings.directory / "dicerobot_{time:YYYY-MM-DD}.log",
        level=settings.level,
        # 与控制台使用同一格式，事件 ID 同样写入文件；serialize 时另在 extra 中单独成字段。
        format=_format_record,
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
