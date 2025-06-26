from contextlib import asynccontextmanager

from fastapi import FastAPI
from apscheduler import AsyncScheduler

from .version import VERSION
from .log import logger, init_logger
from .exception_handlers import init_exception_handlers
from .router import init_router
from .database import init_database, clean_database
from .config import init_config, save_config
from .schedule import init_scheduler, clean_scheduler
from .dispatch import init_dispatcher
from .manage import init_manager, clean_manager


@asynccontextmanager
async def lifespan(_: FastAPI):
    init_logger()

    logger.info(f"DiceRobot {VERSION}")

    init_database()
    init_config()

    async with AsyncScheduler() as scheduler:
        await init_scheduler(scheduler)
        init_manager()
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
