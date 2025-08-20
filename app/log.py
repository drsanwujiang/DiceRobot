import logging
import os
import sys

from loguru import logger

from .globals import DEBUG, LOG_DIR, LOG_LEVEL

MAX_LOG_LENGTH = 1000  # Maximum length of log messages

__all__ = [
    "init_logger"
]


def truncate_formatter(record) -> str:
    if len(record["message"]) > MAX_LOG_LENGTH:
        record["message"] = record["message"][:MAX_LOG_LENGTH] + "..."

    return (
        "<green>{time:YYYY-MM-DD HH:mm:ss.SSS Z}</green> | "
        "<level>{level: <8}</level> | "
        "<cyan>{name}</cyan>:<cyan>{function}</cyan>:<cyan>{line}</cyan> - <level>{message}</level>\n"
    )


def init_logger() -> None:
    logger.remove()

    if DEBUG:
        # Add a console logger for debug mode
        logger.add(sys.stdout, level="DEBUG", diagnose=True)
    else:
        # Disable Uvicorn's default loggers
        logging.getLogger("uvicorn.error").disabled = True
        logging.getLogger("uvicorn.access").disabled = True

    logger.add(
        os.path.join(LOG_DIR, "dicerobot-{time:YYYY-MM-DD}.log"),
        level=LOG_LEVEL,
        format=truncate_formatter,
        rotation="00:00",
        retention="365 days",
        compression="tar.gz"
    )

    logger.debug("Logger initialized")
