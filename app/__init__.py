from contextlib import asynccontextmanager

from fastapi import FastAPI

from .version import VERSION
from .log import init_logger, logger
from .database import init_database, clean_database
from .config import init_config, save_config
from .exceptions import init_handlers
from .routers import init_routers
from .schedule import init_scheduler, clean_scheduler
from .dispatch import init_dispatcher


@asynccontextmanager
async def lifespan(app: FastAPI):
    init_logger()

    logger.info(f"DiceRobot {VERSION}")
    logger.info("Start DiceRobot")

    init_database()
    init_config()

    init_handlers(app)
    init_routers(app)

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
