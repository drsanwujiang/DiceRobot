from contextlib import asynccontextmanager

from fastapi import FastAPI

from .version import VERSION
from .log import logger, init_logger
from .exception_handlers import init_exception_handlers
from .router import init_router
from .database import init_database, clean_database
from .config import init_config, save_config
from .schedule import init_scheduler, clean_scheduler
from .dispatch import init_dispatcher


@asynccontextmanager
async def lifespan(_: FastAPI):
    init_logger()

    logger.info(f"DiceRobot {VERSION}")
    logger.info("Start DiceRobot")

    init_database()
    init_config()

    init_dispatcher()
    init_scheduler()

    logger.success("DiceRobot started")

    yield

    logger.info("Stop DiceRobot")

    clean_scheduler()
    save_config()
    clean_database()

    logger.success("DiceRobot stopped")


dicerobot = FastAPI(
    title="DiceRobot",
    description="A TRPG assistant bot",
    version=VERSION,
    lifespan=lifespan
)

init_exception_handlers(dicerobot)
init_router(dicerobot)
