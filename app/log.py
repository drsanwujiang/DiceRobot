import logging
import os
import sys

from loguru import logger as _logger

from config import settings

# Disable Uvicorn's default loggers
uvicorn_error = logging.getLogger("uvicorn.error").disabled = True
uvicorn_access = logging.getLogger("uvicorn.access").disabled = True

# Maximum length of log messages
MAX_LOG_LENGTH = 1000


def truncate_message(record: dict) -> None:
    if len(record["message"]) > MAX_LOG_LENGTH:
        record["message"] = record["message"][:MAX_LOG_LENGTH] + "..."


logger = _logger.patch(truncate_message)
logger.remove()


def init_logger() -> None:
    if os.environ.get("DICEROBOT_DEBUG"):
        logger.add(sys.stdout, level="DEBUG")

    log_level = os.environ.get("DICEROBOT_LOG_LEVEL") or "INFO"
    logger.add(
        os.path.join(settings.app.dir.logs, "dicerobot-{time:YYYY-MM-DD}.log"),
        level=log_level,
        rotation="00:00",
        retention="365 days",
        compression="tar.gz"
    )

    logger.debug("Logger initialized")
