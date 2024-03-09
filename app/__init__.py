from contextlib import asynccontextmanager

from fastapi import FastAPI

from .version import VERSION
from .log import logger
from .database import init_db, clean_db
from .config import init_config, save_config
from .exceptions import init_handlers
from .routers import init_routers
from .scheduler import init_scheduler, start_scheduler, clean_scheduler
from .internal import init_internal, clean_internal


__version__ = VERSION


@asynccontextmanager
async def lifespan(app: FastAPI):
    logger.success(f"DiceRobot {VERSION} started")

    init_db()
    init_config()
    init_handlers(app)
    init_routers(app)
    init_scheduler()
    init_internal()

    start_scheduler()

    logger.success("DiceRobot initialized")

    yield

    logger.warning("Stopping DiceRobot")

    clean_internal()
    clean_scheduler()
    save_config()
    clean_db()

    logger.success("DiceRobot stopped")


dicerobot = FastAPI(
    title="DiceRobot",
    description="A TRPG game assistant robot",
    version=VERSION,
    lifespan=lifespan,
    license_info={
        "name": "MIT License",
        "url": "https://github.com/drsanwujiang/DiceRobot/blob/master/LICENSE",
    }
)
