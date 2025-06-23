import os
import asyncio
import zipfile
import shutil
import json

from semver.version import Version

from .log import logger
from .config import status, settings
from .schedule import run_task
from .network import client
from .network.cloud import get_versions
from .utils import run_command


class QQManager:
    qq_dir = "/opt/QQ"
    qq_path = os.path.join(qq_dir, "qq")
    package_json_path = os.path.join(qq_dir, "resources/app/package.json")
    qq_config_dir = "/root/.config/QQ"

    def __init__(self):
        self.deb_file: str | None = None
        self.download_process: asyncio.subprocess.Process | None = None
        self.install_process: asyncio.subprocess.Process | None = None

    def is_downloading(self) -> bool:
        return self.download_process is not None and self.download_process.returncode is None

    def is_downloaded(self) -> bool:
        return not self.is_downloading() and self.deb_file is not None and os.path.isfile(self.deb_file)

    def is_installing(self) -> bool:
        return self.install_process is not None and self.install_process.returncode is None

    def is_installed(self) -> bool:
        return not self.is_installing() and os.path.isfile(self.qq_path)

    def get_version(self) -> str | None:
        if not os.path.isfile(self.package_json_path):
            return None

        with open(self.package_json_path, "r", encoding="utf-8") as f:
            try:
                data = json.load(f)
                return data.get("version")
            except ValueError:
                return None

    async def download(self) -> None:
        if self.is_downloading() or self.is_downloaded():
            return

        logger.info("Check latest version of QQ")

        current_version = self.get_version()
        latest_version = Version.parse((await get_versions()).qq)

        if current_version and current_version >= latest_version:
            logger.info("No updates available")
            return

        logger.info(f"Download QQ, version: {latest_version}")

        url = f"{settings.cloud.download.base_url}/qq/linuxqq-{latest_version}.deb"
        self.deb_file = f"/tmp/linuxqq-{latest_version}.deb"

        async with client.stream("GET", url) as response:
            with open(self.deb_file, "wb") as f:
                async for chunk in response.aiter_bytes(chunk_size=8192):
                    f.write(chunk)

        logger.info("QQ downloaded")

    async def install(self) -> None:
        if self.is_installed() or self.is_installing() or not self.is_downloaded():
            return

        logger.info("Install QQ")

        self.install_process = await run_command(f"apt-get install -y -qq {self.deb_file}")

    async def remove(self, purge: bool = False) -> None:
        if not self.is_installed():
            return

        logger.info("Remove QQ")

        await (await run_command("apt-get remove -y -qq linuxqq")).wait()
        shutil.rmtree(self.qq_dir, ignore_errors=True)

        if purge:
            shutil.rmtree(self.qq_config_dir, ignore_errors=True)

    async def stop(self) -> None:
        if self.is_downloading():
            try:
                self.download_process.terminate()
                await asyncio.wait_for(self.download_process.wait(), timeout=3)
            except TimeoutError:
                self.download_process.kill()

        if self.is_installing():
            try:
                self.install_process.terminate()
                await asyncio.wait_for(self.install_process.wait(), timeout=3)
            except TimeoutError:
                self.install_process.kill()

        self.download_process = None
        self.install_process = None


class NapCatManager:
    service_path = "/etc/systemd/system/napcat.service"
    loader_path = os.path.join(QQManager.qq_dir, "resources/app/loadNapCat.js")
    napcat_dir = os.path.join(QQManager.qq_dir, "resources/app/app_launcher/napcat")
    log_dir = os.path.join(napcat_dir, "logs")
    config_dir = os.path.join(napcat_dir, "config")
    env_file = "env"
    env_path = os.path.join(napcat_dir, env_file)
    package_json_file = "package.json"
    package_json_path = os.path.join(napcat_dir, package_json_file)

    napcat_config = {
        "fileLog": True,
        "consoleLog": False,
        "fileLogLevel": "debug" if os.environ.get("DICEROBOT_DEBUG") else "warn",
        "consoleLogLevel": "error",
        "packetBackend": "auto",
        "packetServer": ""
    }
    onebot_config = {
        "network": {
            "httpServers": [
                {
                    "name": "httpServer",
                    "enable": True,
                    "host": str(settings.napcat.api.host),
                    "port": settings.napcat.api.port,
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
                    "url": "http://127.0.0.1:9500/report",
                    "messagePostFormat": "array",
                    "reportSelfMessage": False,
                    "token": settings.security.webhook.secret,
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

    def __init__(self):
        self.zip_file: str | None = None
        self.download_process: asyncio.subprocess.Process | None = None

    def is_downloading(self) -> bool:
        return self.download_process is not None and self.download_process.returncode is None

    def is_downloaded(self) -> bool:
        return not self.is_downloading() and self.zip_file is not None and os.path.isfile(self.zip_file)

    def is_installed(self) -> bool:
        return os.path.isfile(self.package_json_path)

    @staticmethod
    def is_configured() -> bool:
        return settings.napcat.account >= 10000

    @staticmethod
    async def is_running() -> bool:
        return (await (await run_command("systemctl is-active --quiet napcat")).wait()) == 0

    def get_version(self) -> str | None:
        if not self.is_installed():
            return None

        with open(self.package_json_path, "r", encoding="utf-8") as f:
            try:
                data = json.load(f)
                return data.get("version")
            except ValueError:
                return None

    async def download(self) -> None:
        if self.is_downloading() or self.is_downloaded():
            return

        logger.info("Check latest version of NapCat")

        current_version = self.get_version()
        latest_version = Version.parse((await get_versions()).napcat)

        if current_version and current_version >= latest_version:
            logger.info("No updates available")
            return

        logger.info(f"Download NapCat, version: {latest_version}")

        url = f"{settings.cloud.download.base_url}/napcat/napcat-{latest_version}.zip"
        self.zip_file = f"/tmp/napcat-{latest_version}.zip"

        async with client.stream("GET", url) as response:
            with open(self.zip_file, "wb") as f:
                async for chunk in response.aiter_bytes(chunk_size=8192):
                    f.write(chunk)

        logger.info("NapCat downloaded")

    async def install(self) -> None:
        if self.is_installed() or not self.is_downloaded():
            return

        logger.info("Install NapCat")

        # Uncompress NapCat
        with zipfile.ZipFile(self.zip_file, "r") as z:
            z.extractall(self.napcat_dir)

        # Configure systemd
        with open(self.service_path, "w") as f:
            f.write(f"""[Unit]
Description=NapCat service created by DiceRobot
After=network.target

[Service]
Type=simple
User=root
EnvironmentFile={self.env_path}
ExecStart=/usr/bin/xvfb-run -a qq --no-sandbox -q $QQ_ACCOUNT

[Install]
WantedBy=multi-user.target""")

        await (await run_command("systemctl daemon-reload")).wait()

        # Patch QQ
        with open(self.loader_path, "w") as f:
            f.write(f"(async () => {{await import(\"file:///{self.napcat_dir}/napcat.mjs\");}})();")

        with open(QQManager.package_json_path, "r+") as f:
            data = json.load(f)
            data["main"] = "./loadNapCat.js"
            f.seek(0)
            json.dump(data, f, indent=2)
            f.truncate()

        logger.info("NapCat installed")

    async def remove(self) -> None:
        if not self.is_installed() or self.is_running():
            return

        logger.info("Remove NapCat")

        if os.path.isfile(self.service_path):
            os.remove(self.service_path)
            await (await run_command("systemctl daemon-reload")).wait()

        shutil.rmtree(self.napcat_dir, ignore_errors=True)

        logger.info("NapCat removed")

    async def start(self) -> None:
        if not self.is_installed() or not self.is_configured() or self.is_running():
            return

        logger.info("Start NapCat")

        if os.path.isdir(self.log_dir):
            shutil.rmtree(self.log_dir)

        with open(self.env_path, "w") as f:
            f.write(f"QQ_ACCOUNT={settings.napcat.account}")

        with open(os.path.join(self.config_dir, "napcat.json"), "w") as f:
            json.dump(self.napcat_config, f)

        with open(os.path.join(self.config_dir, f"napcat_{settings.napcat.account}.json"), "w") as f:
            json.dump(self.napcat_config, f)

        self.onebot_config["network"]["httpServers"][0]["host"] = str(settings.napcat.api.host)
        self.onebot_config["network"]["httpServers"][0]["port"] = settings.napcat.api.port
        self.onebot_config["network"]["httpClients"][0]["token"] = settings.security.webhook.secret

        with open(os.path.join(self.config_dir, f"onebot11_{settings.napcat.account}.json"), "w") as f:
            json.dump(self.onebot_config, f)

        await (await run_command("systemctl start napcat")).wait()

        logger.info("NapCat started")

    @classmethod
    async def stop(cls) -> None:
        if not cls.is_running():
            return

        logger.info("Stop NapCat")

        await (await run_command("systemctl stop napcat")).wait()

        logger.info("NapCat stopped")

    @classmethod
    def get_logs(cls) -> list[str] | None:
        if not os.path.isdir(cls.log_dir):
            return None

        files = os.listdir(cls.log_dir)

        if not files:
            return None

        for file in files:
            path = os.path.join(cls.log_dir, file)

            if os.path.isfile(path):
                with open(path, "r", encoding="utf-8") as f:
                    return f.readlines()[-100:]


qq_manager = QQManager()
napcat_manager = NapCatManager()


async def init_manager() -> None:
    if all([
        settings.app.start_napcat_at_startup,
        qq_manager.is_installed(),
        napcat_manager.is_installed(),
        napcat_manager.is_configured(),
        not await napcat_manager.is_running()
    ]):
        logger.info("Automatically start NapCat")

        await napcat_manager.start()

    logger.info("Manager initialized")


async def clean_manager() -> None:
    logger.info("Clean manager")

    await qq_manager.stop()
