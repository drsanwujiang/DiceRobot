import os

from loguru import logger as _logger

MAX_LENGTH = 1000


def truncate_message(record: dict) -> None:
    if len(record["message"]) > MAX_LENGTH:
        record["message"] = record["message"][:MAX_LENGTH] + "..."


logger = _logger.patch(truncate_message)
logger.remove()


def init_logger() -> None:
    log_level = os.environ.get("DICEROBOT_LOG_LEVEL") or "INFO"
    logger.add(
        "logs/dicerobot-{time:YYYY-MM-DD}.log",
        level=log_level,
        rotation="1 day",
        retention="6 months",
        compression="tar.gz"
    )

    logger.debug("Logger initialized")
