import os
import sys

from loguru import logger


MAX_LENGTH = 1000


def truncate_message(record: dict) -> None:
    if len(record["message"]) > MAX_LENGTH:
        record["message"] = record["message"][:MAX_LENGTH] + "..."


log_level = os.environ.get("DICEROBOT_LOG_LEVEL") or "SUCCESS"

logger.remove()
logger.add("logs/dicerobot-{time:YYYY-MM-DD}.log", level=log_level, rotation="1 day", retention="6 months", compression="tar.gz")

if os.environ.get("DICEROBOT_DEBUG"):
    logger.add(sys.stdout, level="DEBUG")

logger = logger.patch(truncate_message)
