from contextlib import asynccontextmanager

from fastapi import FastAPI

from .version import VERSION
from .log import logger
from .database import init_db, clean_db
from .config import init_config, save_config
from .exceptions import init_handlers
from .routers import init_routers
from .internal import init_internal, clean_internal


__version__ = VERSION


@asynccontextmanager
async def lifespan(_app: FastAPI):
    yield

    logger.warning("Stopping DiceRobot")

    clean_internal()
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

logger.success(f"DiceRobot {VERSION} started")

init_db()
init_config()
init_handlers(dicerobot)
init_routers(dicerobot)
init_internal()

logger.success("DiceRobot initialized")
