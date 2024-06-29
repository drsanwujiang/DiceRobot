from typing import Union
import sys
import os
import datetime
import tarfile

from loguru import logger as _logger

LOG_DIR = os.path.join(os.getcwd(), "logs")
TEMP_LOG_DIR = "/temp/dicerobot-logs"
MAX_LENGTH = 1000
MAX_FILE_SIZE = 5 * 1024 * 1024  # 5 MB


def truncate_message(record: dict) -> None:
    if len(record["message"]) > MAX_LENGTH:
        record["message"] = record["message"][:MAX_LENGTH] + "..."


logger = _logger.patch(truncate_message)
logger.remove()


def init_logger() -> None:
    if os.environ.get("DICEROBOT_DEBUG"):
        logger.add(sys.stdout, level="DEBUG")
    else:
        log_level = os.environ.get("DICEROBOT_LOG_LEVEL") or "INFO"
        logger.add(
            os.path.join(LOG_DIR, "dicerobot-{time:YYYY-MM-DD}.log"),
            level=log_level,
            rotation="1 day",
            retention="6 months",
            compression="tar.gz"
        )

    logger.debug("Logger initialized")


def load_logs(date: datetime.date) -> Union[list[str], None, False]:
    date = date.strftime("%Y-%m-%d")
    file = f"dicerobot-{date}.log"
    log_file = os.path.join(LOG_DIR, file)
    compressed_file = os.path.join(LOG_DIR, f"{file}.tar.gz")
    temp_log_file = os.path.join(TEMP_LOG_DIR, file)

    if os.path.isfile(log_file):
        if os.stat(log_file).st_size > MAX_FILE_SIZE:
            return False

        with open(log_file, "r", encoding="utf-8") as f:
            return f.readlines()
    elif os.path.isfile(compressed_file):
        with tarfile.open(compressed_file, "r:gz") as tar:
            tar.extract(file, TEMP_LOG_DIR)

        if os.stat(temp_log_file).st_size > MAX_FILE_SIZE:
            return False

        with open(temp_log_file, "r", encoding="utf-8") as file:
            return file.readlines()
    else:
        return None
