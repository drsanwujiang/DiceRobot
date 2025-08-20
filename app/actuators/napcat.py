from typing import TYPE_CHECKING
import os
import json
import zipfile
import shutil

from loguru import logger
import aiofiles
from semver.version import Version

from ..utils import run_command_wait
from . import Actuator, LogStreamer

if TYPE_CHECKING:
    from ..context import AppContext

__all__ = [
    "NapCatActuator"
]


class NapCatActuator(Actuator, LogStreamer):
    name = "NapCat"

    def __init__(self, context: "AppContext") -> None:
        super().__init__(context=context, logs_dir=context.settings.napcat.dir.logs)

        self.service_path = "/etc/systemd/system/napcat.service"
        self.env_path = "env"
        self.package_json_path = "package.json"
        self.napcat_config = {
            "fileLog": True,
            "consoleLog": False,
            "fileLogLevel": "debug" if context.status.debug else "warn",
            "consoleLogLevel": "error",
            "packetBackend": "auto",
            "packetServer": ""
        }
        self.onebot_config = {
            "network": {
                "httpServers": [
                    {
                        "name": "httpServer",
                        "enable": True,
                        "host": str(context.settings.napcat.api.host),
                        "port": context.settings.napcat.api.port,
                        "enableCors": True,
                        "enableWebsocket": True,
                        "messagePostFormat": "array",
                        "token": "",
                        "debug": False
                    }
                ],
                "httpClients": [
                    {
                        "name": "httpClient",
                        "enable": True,
                        "url": "https://127.0.0.1:9500/report",
                        "messagePostFormat": "array",
                        "reportSelfMessage": False,
                        "token": context.settings.security.webhook.secret,
                        "debug": False
                    }
                ],
                "websocketServers": [],
                "websocketClients": []
            },
            "musicSignUrl": "",
            "enableLocalFile2Url": False,
            "parseMultMsg": False
        }

    @property
    def _download_filename(self) -> str:
        return "napcat-{version}.zip"

    @property
    def installed(self) -> bool:
        return os.path.isfile(os.path.join(self.context.settings.napcat.dir.base, self.package_json_path))

    @property
    def configured(self) -> bool:
        return self.context.settings.napcat.account >= 10000

    async def cleanup(self):
        logger.debug("Clean NapCat actuator")

        await super().cleanup()

    @staticmethod
    async def check_running() -> bool:
        return (await run_command_wait("systemctl is-active --quiet napcat")) == 0

    async def get_version(self) -> str | None:
        if not self.installed:
            return None

        async with aiofiles.open(
            os.path.join(self.context.settings.napcat.dir.base, self.package_json_path),
            "r",
            encoding="utf-8"
        ) as f:
            try:
                data = json.loads(await f.read())
                return data.get("version")
            except ValueError:
                return None

    async def _get_latest_version(self) -> Version:
        return Version.parse((await self.context.network_manager.cloud.get_versions()).data.napcat)

    def get_log_file(self) -> str | None:
        if not os.path.isdir(self.context.settings.napcat.dir.logs) or not (files := os.listdir(self.context.settings.napcat.dir.logs)):
            return None

        for file in files:
            if os.path.isfile(os.path.join(self.context.settings.napcat.dir.logs, file)):
                return file

        return None

    async def _install(self, filepath: str) -> bool:
        await self.remove()

        logger.info(f"Extract files")

        with zipfile.ZipFile(filepath, "r") as z:
            z.extractall(self.context.settings.napcat.dir.base)

        # Configure systemd
        async with aiofiles.open(self.service_path, "w", encoding="utf-8") as f:
            await f.write(f"""[Unit]
        Description=NapCat service created by DiceRobot
        After=network.target

        [Service]
        Type=simple
        User=root
        EnvironmentFile={os.path.join(self.context.settings.napcat.dir.base, self.env_path)}
        ExecStart=/usr/bin/xvfb-run -a qq --no-sandbox -q $QQ_ACCOUNT

        [Install]
        WantedBy=multi-user.target""")

        await run_command_wait("systemctl daemon-reload")

        # Patch QQ
        await self.context.qq_actuator.patch()

        return True

    async def remove(self) -> None:
        logger.info("Remove NapCat")

        if os.path.isfile(self.service_path):
            os.remove(self.service_path)
            await run_command_wait("systemctl daemon-reload")

        shutil.rmtree(self.context.settings.napcat.dir.base, ignore_errors=True)

        logger.info("NapCat removed")

    async def start(self) -> None:
        logger.info("Start NapCat")

        # Remove old logs
        if os.path.isdir(self.context.settings.napcat.dir.logs):
            shutil.rmtree(self.context.settings.napcat.dir.logs)

        # Setup environment variables
        async with aiofiles.open(
            os.path.join(self.context.settings.napcat.dir.base, self.env_path),
            "w",
            encoding="utf-8"
        ) as f:
            await f.write(f"QQ_ACCOUNT={self.context.settings.napcat.account}\n")
            await f.write(f"NODE_EXTRA_CA_CERTS={self.context.settings.app.dir.base}/certificates/ca.crt")

        # Setup NapCat configuration
        async with aiofiles.open(
            os.path.join(self.context.settings.napcat.dir.config, "napcat.json"),
            "w",
            encoding="utf-8"
        ) as f:
            await f.write(json.dumps(self.napcat_config))

        async with aiofiles.open(
            os.path.join(self.context.settings.napcat.dir.config, f"napcat_{self.context.settings.napcat.account}.json"),
            "w",
            encoding="utf-8"
        ) as f:
            await f.write(json.dumps(self.napcat_config))

        # Setup OneBot configuration
        self.onebot_config["network"]["httpServers"][0]["host"] = str(self.context.settings.napcat.api.host)
        self.onebot_config["network"]["httpServers"][0]["port"] = self.context.settings.napcat.api.port
        self.onebot_config["network"]["httpClients"][0]["token"] = self.context.settings.security.webhook.secret

        async with aiofiles.open(
            os.path.join(self.context.settings.napcat.dir.config, f"onebot11_{self.context.settings.napcat.account}.json"),
            "w",
            encoding="utf-8"
        ) as f:
            await f.write(json.dumps(self.onebot_config))

        await run_command_wait("systemctl start napcat")

        logger.info("NapCat started")

    @staticmethod
    async def stop() -> None:
        logger.info("Stop NapCat")

        await run_command_wait("systemctl stop napcat")

        logger.info("NapCat stopped")
