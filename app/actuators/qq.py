from typing import TYPE_CHECKING
import os
import json
import shutil

from loguru import logger
import aiofiles
from semver.version import Version

from ..utils import run_command_wait
from . import Actuator

if TYPE_CHECKING:
    from ..context import AppContext

__all__ = [
    "QQActuator"
]


class QQActuator(Actuator):
    name = "QQ"

    def __init__(self, context: "AppContext") -> None:
        super().__init__(context=context)

        self.package_json_path = "resources/app/package.json"
        self.loader_path = "resources/app/loadNapCat.js"

    @property
    def _download_filename(self) -> str:
        return "linuxqq-{version}.deb"

    @property
    def installed(self) -> bool:
        return os.path.isfile(os.path.join(self.context.settings.qq.dir.base, self.package_json_path))

    async def get_version(self) -> str | None:
        if not self.installed:
            return None

        async with aiofiles.open(
            os.path.join(self.context.settings.qq.dir.base, self.package_json_path),
            "r",
            encoding="utf-8"
        ) as f:
            try:
                data = json.loads(await f.read())
                return data.get("version")
            except ValueError:
                return None

    async def _get_latest_version(self) -> Version:
        return Version.parse((await self.context.network_manager.cloud.get_versions()).data.qq)

    async def _install(self, filepath: str) -> bool:
        await self.remove()

        logger.info("Install Debian package")

        if (await run_command_wait(f"apt-get install -y -qq {filepath}")) != 0:
            logger.error("Failed to install Debian package")
            return False

        return True

    async def patch(self) -> None:
        logger.info("Patch QQ")

        async with aiofiles.open(
            os.path.join(self.context.settings.qq.dir.base, self.loader_path),
            "w",
            encoding="utf-8"
        ) as f:
            await f.write(
                f"(async () => {{await import(\"file:///{self.context.settings.napcat.dir.base}/napcat.mjs\");}})();"
            )

        async with aiofiles.open(
            os.path.join(self.context.settings.qq.dir.base, self.package_json_path),
            "r+",
            encoding="utf-8"
        ) as f:
            data = json.loads(await f.read())
            data["main"] = "./loadNapCat.js"
            await f.seek(0)
            await f.write(json.dumps(data, indent=2))
            await f.truncate()

    async def remove(self, purge: bool = False) -> None:
        logger.info("Remove QQ")

        await run_command_wait("apt-get remove -y -qq linuxqq")
        shutil.rmtree(self.context.settings.qq.dir.base, ignore_errors=True)

        if purge:
            shutil.rmtree(self.context.settings.qq.dir.config, ignore_errors=True)

        logger.info("QQ removed")
