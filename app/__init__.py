from contextlib import asynccontextmanager

from fastapi import FastAPI
from apscheduler import AsyncScheduler

from .version import VERSION
from .database import init_database, clean_database
from .config import load_config, save_config
from .log import logger, init_logger
from .schedule import init_scheduler, clean_scheduler
from .manage import init_manager
from .dispatch import init_dispatcher
from .exception_handlers import init_exception_handlers
from .router import init_router


@asynccontextmanager
async def lifespan(_: FastAPI):
    init_database()
    load_config()
    init_logger()

    logger.info(f"DiceRobot {VERSION}")

    async with AsyncScheduler() as scheduler:
        await init_scheduler(scheduler)
        await init_manager()
        await init_dispatcher()

        logger.success("DiceRobot started")

        yield

        await clean_scheduler()

    save_config()
    clean_database()

    logger.warning("DiceRobot stopped")


dicerobot = FastAPI(
    title="DiceRobot",
    description="A TRPG assistant bot",
    version=VERSION,
    lifespan=lifespan
)

init_exception_handlers(dicerobot)
init_router(dicerobot)
