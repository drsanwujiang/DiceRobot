from typing import TYPE_CHECKING
import os
import zipfile
import shutil
import signal

from loguru import logger
from semver.version import Version

from ..utils import run_command_wait
from . import Actuator, LogStreamer

if TYPE_CHECKING:
    from ..context import AppContext

__all__ = [
    "AppActuator"
]


class AppActuator(Actuator, LogStreamer):
    name = "DiceRobot"

    def __init__(self, context: "AppContext") -> None:
        super().__init__(context=context, logs_dir=context.settings.app.dir.logs)

    @property
    def _download_filename(self) -> str:
        return "dicerobot-{version}.zip"

    async def initialize(self) -> None:
        self._ensure_directory()

        logger.debug("Application actuator initialized")

    async def cleanup(self):
        logger.debug("Clean application actuator")

        await super().cleanup()
        shutil.rmtree(self.context.settings.app.dir.temp, ignore_errors=True)  # Clean temporary files

    def _ensure_directory(self) -> None:
        os.makedirs(self.context.settings.app.dir.temp, exist_ok=True)

    async def get_version(self) -> str:
        return self.context.status.version

    async def _get_latest_version(self) -> Version:
        return Version.parse((await self.context.network_manager.cloud.get_versions()).data.dicerobot)

    async def _install(self, filepath: str) -> bool:
        logger.info(f"Extract files")

        with zipfile.ZipFile(filepath, "r") as z:
            z.extractall(self.context.settings.app.dir.base)

        logger.info("Update dependencies")

        if (await run_command_wait("poetry lock")) != 0 or (await run_command_wait("poetry update")) != 0:
            logger.error("Failed to update dependencies")
            return False

        return True

    async def restart(self) -> None:
        logger.warning(f"Restart application")

        await self.context.task_manager.run_task_later("dicerobot.restart", 1)

    @staticmethod
    def stop() -> None:
        logger.warning(f"Stop application")

        signal.raise_signal(signal.SIGTERM)
