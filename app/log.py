import sys

from loguru import logger


FORMAT = "<green>{time:YYYY-MM-DD HH:mm:ss.SSS}</green> | <level>{level: <8}</level> | <cyan>{name}</cyan>:<cyan>{function}</cyan>:<cyan>{line}</cyan> - <level>{message}</level>\n"
MAX_LENGTH = 1000


def format_message(record: dict) -> str:
    if len(record["message"]) > MAX_LENGTH:
        record["message"] = record["message"][:MAX_LENGTH] + "..."

    return FORMAT


logger.remove()
logger.add(sys.stdout, level="DEBUG", format=format_message)
logger.add("logs/dicerobot-{time:YYYY-MM-DD}.log", level="DEBUG", format=format_message, rotation="1 day", retention="6 months", compression="tar.gz")
