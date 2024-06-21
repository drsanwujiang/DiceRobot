import os
import asyncio
from datetime import date
import zipfile
import shutil

from ruamel.yaml import YAML
from ruamel.yaml.scalarstring import DoubleQuotedScalarString

from .log import logger
from .config import settings
from .network.dicerobot import download_mirai

yaml = YAML()
yaml.indent(mapping=2, sequence=4, offset=2)


class MiraiManager:
    def __init__(self):
        self.process: asyncio.subprocess.Process | None = None

    @staticmethod
    def init_api_config() -> None:
        config = {
            "adapters": [
                "http",
                "webhook"
            ],
            "enableVerify": False,
            "verifyKey": 12345678,
            "singleMode": True,
            "cacheSize": 4096,
            "adapterSettings": {
                "http": {
                    "host": settings.mirai.api.host,
                    "port": settings.mirai.api.port,
                    "cors": [
                        DoubleQuotedScalarString("*")
                    ]
                },
                "webhook": {
                    "destinations": [
                        DoubleQuotedScalarString(f"http://127.0.0.1:9500/report?token={settings.security.webhook.token}")
                    ]
                }
            }
        }

        with open(os.path.join(settings.mirai.dir.config_api, settings.mirai.file.config_api), "w", encoding="utf-8") as f:
            yaml.dump(config, f)

    @staticmethod
    def is_installed() -> bool:
        return os.path.exists(os.path.join(settings.mirai.dir.base, settings.mirai.file.mcl))

    def is_running(self) -> bool:
        return self.process is not None and self.process.returncode is None

    @staticmethod
    def get_log(date_: date) -> list[str] | None:
        path = os.path.join(settings.mirai.dir.logs, f"{date_.isoformat()}.log")

        if not os.path.exists(path):
            return None

        with open(path, "r", encoding="utf-8") as file:
            return file.readlines()[-100:]

    @classmethod
    def install(cls) -> None:
        if cls.is_installed():
            return

        logger.info("Install Mirai")

        file = download_mirai()

        with zipfile.ZipFile(file, "r") as z:
            z.extractall(settings.mirai.dir.base)

        os.remove(file)

        logger.info("Mirai installed")

    @classmethod
    def remove(cls) -> None:
        if not cls.is_installed():
            return

        logger.info("Remove Mirai")

        shutil.rmtree(settings.mirai.dir.base)

        logger.info("Mirai removed")

    @staticmethod
    def get_autologin_config() -> dict:
        with open(os.path.join(settings.mirai.dir.config_console, settings.mirai.file.config_autologin), "r", encoding="utf-8") as f:
            return yaml.load(f)

    @staticmethod
    def set_autologin_config(config: dict) -> None:
        with open(os.path.join(settings.mirai.dir.config_console, settings.mirai.file.config_autologin), "w", encoding="utf-8") as f:
            yaml.dump(config, f)

    async def update(self) -> None:
        if not self.is_installed() or self.is_running():
            return

        logger.info("Update Mirai")

        self.process = await asyncio.create_subprocess_exec(
            "java", f"-Dmirai.console.skip-end-user-readme", "-jar", settings.mirai.file.mcl, "--disable-progress-bar", "--dry-run",
            stdin=asyncio.subprocess.PIPE,
            stdout=asyncio.subprocess.DEVNULL,
            stderr=asyncio.subprocess.DEVNULL,
            cwd=settings.mirai.dir.base
        )

        logger.info("Mirai updated")

    async def start(self) -> None:
        if not self.is_installed() or self.is_running():
            return

        logger.info("Initialize Mirai API config")

        self.init_api_config()

        logger.info("Start Mirai")

        self.process = await asyncio.create_subprocess_exec(
            "java", "-Dmirai.console.skip-end-user-readme", "-jar", settings.mirai.file.mcl, "--disable-progress-bar", "--boot-only",
            stdin=asyncio.subprocess.PIPE,
            stdout=asyncio.subprocess.DEVNULL,
            stderr=asyncio.subprocess.DEVNULL,
            cwd=settings.mirai.dir.base
        )

        logger.info("Mirai started")

    async def stop(self) -> None:
        if self.is_running():
            logger.info("Stop Mirai")

            try:
                self.process.terminate()
                await asyncio.wait_for(self.process.wait(), 5)

                logger.info("Mirai stopped")
            except (TimeoutError, asyncio.TimeoutError):
                logger.warning("Failed to stop Mirai, kill process")

                self.process.kill()

        self.process = None

    def input(self, data: str) -> None:
        data += "\n"

        if self.is_running():
            self.process.stdin.write(data.encode())


mirai_manager = MiraiManager()


def init_manager() -> None:
    logger.info("Manager initialized")


async def clean_manager() -> None:
    logger.info("Clean manager")

    await mirai_manager.stop()
