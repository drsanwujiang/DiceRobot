from contextlib import asynccontextmanager

from loguru import logger
from fastapi import FastAPI
from apscheduler import AsyncScheduler

from .globals import VERSION
from .log import init_logger
from .context import AppContext
from .managers import Manager
from .managers.database import DatabaseManager
from .managers.config import ConfigManager
from .managers.data import DataManager
from .managers.dispatch import DispatchManager
from .managers.task import TaskManager
from .managers.network import NetworkManager
from .actuators import Actuator
from .actuators.app import AppActuator
from .actuators.qq import QQActuator
from .actuators.napcat import NapCatActuator
from .exception_handlers import init_exception_handlers
from .routers import init_routers

__all__ = [
    "dicerobot"
]


@asynccontextmanager
async def lifespan(app: FastAPI):
    init_logger()
    logger.info(f"DiceRobot {VERSION}")

    async with AsyncScheduler() as scheduler:
        app.state.context = AppContext()
        app.state.context.scheduler = scheduler
        app.state.context.database_manager = DatabaseManager(app.state.context)
        app.state.context.config_manager = ConfigManager(app.state.context)
        app.state.context.data_manager = DataManager(app.state.context)
        app.state.context.dispatch_manager = DispatchManager(app.state.context)
        app.state.context.task_manager = TaskManager(app.state.context)
        app.state.context.network_manager = NetworkManager(app.state.context)
        app.state.context.app_actuator = AppActuator(app.state.context)
        app.state.context.qq_actuator = QQActuator(app.state.context)
        app.state.context.napcat_actuator = NapCatActuator(app.state.context)

        components = [
            app.state.context.database_manager,
            app.state.context.config_manager,
            app.state.context.data_manager,
            app.state.context.dispatch_manager,
            app.state.context.network_manager,
            app.state.context.task_manager,
            app.state.context.app_actuator,
            app.state.context.qq_actuator,
            app.state.context.napcat_actuator
        ]

        for component in components:
            await component.initialize()

        logger.success("DiceRobot started")
        yield

        for component in reversed(components):
            await component.cleanup()

        logger.warning("DiceRobot stopped")


dicerobot = FastAPI(
    title="DiceRobot",
    description="A TRPG assistant bot",
    version=VERSION,
    lifespan=lifespan
)

init_exception_handlers(dicerobot)
init_routers(dicerobot)
