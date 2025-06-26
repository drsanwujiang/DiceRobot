import os
import zipfile
import shutil
import json
import signal

from semver.version import Version

from .log import logger
from .config import status, settings
from .enum import UpdateStatus
from .schedule import run_task
from .network import client
from .network.cloud import get_versions
from .utils import run_command


class DiceRobotManager:
    def __init__(self):
        self.update_status = UpdateStatus.NONE

    async def update(self) -> None:
        logger.info("Check latest version of Dicerobot")

        self.update_status = UpdateStatus.CHECKING
        latest_version = Version.parse((await get_versions()).dicerobot)

        if status.version >= latest_version:
            logger.info("No updates available")
            self.update_status = UpdateStatus.COMPLETED
            return

        logger.info(f"Download DiceRobot, version: {latest_version}")

        self.update_status = UpdateStatus.DOWNLOADING
        url = f"{settings.cloud.download.base_url}/dicerobot/dicerobot-{latest_version}.zip"
        zip_file = f"/tmp/dicerobot-{latest_version}.zip"

        try:
            async with client.stream("GET", url) as response:
                with open(zip_file, "wb") as f:
                    async for chunk in response.aiter_bytes(chunk_size=8192):
                        f.write(chunk)
        except:
            logger.error("Failed to download")
            self.update_status = UpdateStatus.FAILED
            return

        logger.info(f"Extract files")

        self.update_status = UpdateStatus.INSTALLING

        with zipfile.ZipFile(zip_file, "r") as z:
            z.extractall()

        os.remove(zip_file)

        logger.info("Update dependencies")

        if (await (await run_command("poetry lock")).wait()) != 0 or \
           (await (await run_command("poetry update")).wait()) != 0:
            logger.error("Failed to update dependencies")
            self.update_status = UpdateStatus.FAILED

        logger.success(f"Update completed")

        self.update_status = UpdateStatus.COMPLETED

    @staticmethod
    async def restart() -> None:
        logger.warning(f"Restart application")

        await run_task("dicerobot.restart", 1)

    @staticmethod
    def stop() -> None:
        logger.warning(f"Stop application")

        signal.raise_signal(signal.SIGTERM)


class QQManager:
    root_dir = "/opt/QQ"
    package_json_path = os.path.join(root_dir, "resources/app/package.json")
    config_dir = "/root/.config/QQ"

    def __init__(self):
        self.update_status = UpdateStatus.NONE

    def installed(self) -> bool:
        return os.path.isfile(self.package_json_path)

    def get_version(self) -> str | None:
        if not self.installed():
            return None

        with open(self.package_json_path, "r", encoding="utf-8") as f:
            try:
                data = json.load(f)
                return data.get("version")
            except ValueError:
                return None

    async def update(self) -> None:
        logger.info("Check latest version of QQ")

        self.update_status = UpdateStatus.CHECKING
        latest_version = (await get_versions()).qq

        logger.info(f"Download QQ, version: {latest_version}")

        self.update_status = UpdateStatus.DOWNLOADING
        url = f"{settings.cloud.download.base_url}/qq/linuxqq-{latest_version}.deb"
        deb_file = f"/tmp/linuxqq-{latest_version}.deb"

        try:
            async with client.stream("GET", url) as response:
                with open(deb_file, "wb") as f:
                    async for chunk in response.aiter_bytes(chunk_size=8192):
                        f.write(chunk)
        except:
            logger.error("Failed to download")
            self.update_status = UpdateStatus.FAILED
            return

        logger.info("Install Debian package")

        self.update_status = UpdateStatus.INSTALLING

        if (await (await run_command(f"apt-get install -y -qq {deb_file}")).wait()) != 0:
            logger.error("Failed to install Debian package")
            self.update_status = UpdateStatus.FAILED
            return

        os.remove(deb_file)

        logger.success("Update completed")

        self.update_status = UpdateStatus.COMPLETED

    async def remove(self, purge: bool = False) -> None:
        logger.info("Remove QQ")

        await (await run_command("apt-get remove -y -qq linuxqq")).wait()
        shutil.rmtree(self.root_dir, ignore_errors=True)

        if purge:
            shutil.rmtree(self.config_dir, ignore_errors=True)


class NapCatManager:
    service_path = "/etc/systemd/system/napcat.service"
    loader_path = os.path.join(QQManager.root_dir, "resources/app/loadNapCat.js")
    root_dir = os.path.join(QQManager.root_dir, "resources/app/app_launcher/napcat")
    log_dir = os.path.join(root_dir, "logs")
    config_dir = os.path.join(root_dir, "config")
    env_file = "env"
    env_path = os.path.join(root_dir, env_file)
    package_json_file = "package.json"
    package_json_path = os.path.join(root_dir, package_json_file)

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
        self.update_status = UpdateStatus.NONE

    def installed(self) -> bool:
        return os.path.isfile(self.package_json_path)

    @staticmethod
    def configured() -> bool:
        return settings.napcat.account >= 10000

    @staticmethod
    async def running() -> bool:
        return (await (await run_command("systemctl is-active --quiet napcat")).wait()) == 0

    def get_version(self) -> str | None:
        if not self.installed():
            return None

        with open(self.package_json_path, "r", encoding="utf-8") as f:
            try:
                data = json.load(f)
                return data.get("version")
            except ValueError:
                return None

    async def update(self) -> None:
        logger.info("Check latest version of NapCat")

        self.update_status = UpdateStatus.CHECKING
        latest_version = (await get_versions()).napcat

        logger.info(f"Download NapCat, version: {latest_version}")

        self.update_status = UpdateStatus.DOWNLOADING
        url = f"{settings.cloud.download.base_url}/napcat/napcat-{latest_version}.zip"
        zip_file = f"/tmp/napcat-{latest_version}.zip"

        try:
            async with client.stream("GET", url) as response:
                with open(zip_file, "wb") as f:
                    async for chunk in response.aiter_bytes(chunk_size=8192):
                        f.write(chunk)
        except:
            logger.error("Failed to download")
            self.update_status = UpdateStatus.FAILED
            return

        logger.info(f"Extract files")

        self.update_status = UpdateStatus.INSTALLING

        with zipfile.ZipFile(zip_file, "r") as z:
            z.extractall(self.root_dir)

        os.remove(zip_file)

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
            f.write(f"(async () => {{await import(\"file:///{self.root_dir}/napcat.mjs\");}})();")

        with open(QQManager.package_json_path, "r+") as f:
            data = json.load(f)
            data["main"] = "./loadNapCat.js"
            f.seek(0)
            json.dump(data, f, indent=2)
            f.truncate()

        logger.success("Update completed")

        self.update_status = UpdateStatus.COMPLETED

    async def remove(self) -> None:
        logger.info("Remove NapCat")

        if os.path.isfile(self.service_path):
            os.remove(self.service_path)
            await (await run_command("systemctl daemon-reload")).wait()

        shutil.rmtree(self.root_dir, ignore_errors=True)

        logger.info("NapCat removed")

    async def start(self) -> None:
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

        return None


dicerobot_manager = DiceRobotManager()
qq_manager = QQManager()
napcat_manager = NapCatManager()


async def init_manager() -> None:
    if all([
        settings.app.start_napcat_at_startup,
        qq_manager.installed(),
        napcat_manager.installed(),
        napcat_manager.configured(),
        not await napcat_manager.running()
    ]):
        logger.info("Automatically start NapCat")

        await napcat_manager.start()

    logger.info("Manager initialized")
