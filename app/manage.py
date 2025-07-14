import os
import asyncio
import tarfile
import zipfile
import shutil
import json
import signal
from collections.abc import AsyncGenerator

from loguru import logger
from watchfiles import Change, awatch
import aiofiles
from semver.version import Version

from .config import status, settings
from .enum import UpdateStatus
from .schedule import run_task
from .network import client
from .network.cloud import get_versions
from .utils import run_command


class FileWatcher:
    def __init__(self, filepath: str):
        self.filepath = filepath
        self.queues: list[asyncio.Queue] = []
        self.task: asyncio.Task | None = None

    async def watch_loop(self):
        try:
            async with aiofiles.open(self.filepath, mode="r") as f:
                await f.seek(0, os.SEEK_END)

                async for changes in awatch(self.filepath):
                    for change, path in changes:
                        if change == Change.modified:
                            lines = await f.readlines()

                            if lines:
                                for queue in self.queues:
                                    queue.put_nowait(lines)
        except asyncio.CancelledError:
            logger.debug("File watcher cancelled")

    def start(self):
        logger.debug(f"Start watching file: {self.filepath}")

        self.task = asyncio.create_task(self.watch_loop())

    def stop(self):
        if self.task:
            logger.debug(f"Stop watching file: {self.filepath}")

            self.task.cancel()

    async def subscribe(self) -> asyncio.Queue:
        queue = asyncio.Queue()
        self.queues.append(queue)

        return queue

    def unsubscribe(self, queue: asyncio.Queue):
        if queue in self.queues:
            self.queues.remove(queue)


class LogHelper:
    def __init__(self, logs_dir: str):
        self.logs_dir = logs_dir
        self.watchers: dict[str, FileWatcher] = {}
        self.lock = asyncio.Lock()

    def check(self, filename: str) -> bool:
        logger.debug(f"Check log file: {filename}")

        if os.path.isfile(os.path.join(self.logs_dir, filename)):
            return True
        elif os.path.isfile(compressed_file := os.path.join(self.logs_dir, f"{filename}.tar.gz")):
            logger.info(f"Decompress log file: {compressed_file}.tar.gz")

            with tarfile.open(compressed_file, "r:gz") as tar:
                tar.extract(filename, self.logs_dir)

            return True

        return False

    async def load(self, filename: str) -> AsyncGenerator[list[str]]:
        logger.debug(f"Load log file: {filename}")

        async with aiofiles.open(os.path.join(self.logs_dir, filename), "r", encoding="utf-8") as f:
            batch = []

            async for line in f:
                batch.append(line)

                if len(batch) >= 100:
                    yield batch
                    batch = []
                    await asyncio.sleep(0.01)

            if batch:
                yield batch

    async def subscribe(self, filename: str) -> asyncio.Queue:
        async with self.lock:
            logger.debug(f"Subscribe to log file: {filename}")

            if filename not in self.watchers:
                self.watchers[filename] = FileWatcher(os.path.join(self.logs_dir, filename))
                self.watchers[filename].start()

            return await self.watchers[filename].subscribe()

    async def unsubscribe(self, filename: str, queue: asyncio.Queue) -> None:
        async with self.lock:
            if filename in self.watchers:
                logger.debug(f"Unsubscribe from log file: {filename}")

                watcher = self.watchers[filename]
                watcher.unsubscribe(queue)

                if len(watcher.queues) == 0:
                    watcher.stop()
                    del self.watchers[filename]

    async def clean(self):
        async with self.lock:
            logger.debug("Clean log watchers")

            for filename, watcher in self.watchers.items():
                watcher.stop()

                # Clean temporary files
                if os.path.isfile(os.path.join(self.logs_dir, f"{filename}.tar.gz")):
                    os.remove(os.path.join(self.logs_dir, filename))


class Manager:
    def __init__(self):
        self.update_status = UpdateStatus.NONE


class LogManager(Manager):
    def __init__(self, logs_dir: str):
        super().__init__()
        self.log = LogHelper(logs_dir)

    async def clean(self):
        await self.log.clean()


class DiceRobotManager(LogManager):
    def __init__(self):
        super().__init__(settings.app.dir.logs)

    @staticmethod
    def ensure_directory() -> None:
        os.makedirs(settings.app.dir.temp, exist_ok=True)

    async def update(self) -> None:
        logger.info("Check latest version of Dicerobot")

        self.update_status = UpdateStatus.CHECKING
        latest_version = Version.parse((await get_versions()).data.dicerobot)

        if status.version >= latest_version:
            logger.info("No updates available")
            self.update_status = UpdateStatus.COMPLETED
            return

        logger.info(f"Download DiceRobot, version: {latest_version}")

        self.update_status = UpdateStatus.DOWNLOADING
        url = f"{settings.cloud.download.base_url}/dicerobot/dicerobot-{latest_version}.zip"
        zip_file = f"{settings.app.dir.temp}/dicerobot-{latest_version}.zip"

        try:
            async with client.stream("GET", url) as response:
                with aiofiles.open(zip_file, "wb") as f:
                    async for chunk in response.aiter_bytes(chunk_size=8192):
                        await f.write(chunk)
        except:
            logger.exception("Failed to download")
            self.update_status = UpdateStatus.FAILED
            return

        logger.info(f"Extract files")

        self.update_status = UpdateStatus.INSTALLING

        with zipfile.ZipFile(zip_file, "r") as z:
            z.extractall(settings.app.dir.base)

        os.remove(zip_file)

        logger.info("Update dependencies")

        if (await (await run_command("poetry lock")).wait()) != 0 or \
           (await (await run_command("poetry update")).wait()) != 0:
            logger.error("Failed to update dependencies")
            self.update_status = UpdateStatus.FAILED

        logger.success(f"DiceRobot update completed")

        self.update_status = UpdateStatus.COMPLETED

    @staticmethod
    async def restart() -> None:
        logger.warning(f"Restart application")

        await run_task("dicerobot.restart", 1)

    @staticmethod
    def stop() -> None:
        logger.warning(f"Stop application")

        signal.raise_signal(signal.SIGTERM)

    async def clean(self):
        logger.debug("Clean DiceRobot manager")

        await super().clean()
        shutil.rmtree(settings.app.dir.temp, ignore_errors=True)  # Clean temporary files


class QQManager(Manager):
    package_json_path = os.path.join(settings.qq.dir.base, "resources/app/package.json")

    def installed(self) -> bool:
        return os.path.isfile(self.package_json_path)

    async def get_version(self) -> str | None:
        if not self.installed():
            return None

        async with aiofiles.open(self.package_json_path, "r", encoding="utf-8") as f:
            try:
                data = json.loads(await f.read())
                return data.get("version")
            except ValueError:
                return None

    async def update(self) -> None:
        logger.info("Check latest version of QQ")

        self.update_status = UpdateStatus.CHECKING
        latest_version = (await get_versions()).data.qq

        logger.info(f"Download QQ, version: {latest_version}")

        self.update_status = UpdateStatus.DOWNLOADING
        url = f"{settings.cloud.download.base_url}/qq/linuxqq-{latest_version}.deb"
        deb_file = f"{settings.app.dir.temp}/linuxqq-{latest_version}.deb"

        try:
            async with client.stream("GET", url) as response:
                async with aiofiles.open(deb_file, "wb") as f:
                    async for chunk in response.aiter_bytes(chunk_size=8192):
                        await f.write(chunk)
        except:
            logger.exception("Failed to download")
            self.update_status = UpdateStatus.FAILED
            return

        logger.info("Install Debian package")

        self.update_status = UpdateStatus.INSTALLING

        if (await (await run_command(f"apt-get install -y -qq {deb_file}")).wait()) != 0:
            logger.error("Failed to install Debian package")
            self.update_status = UpdateStatus.FAILED
            return

        os.remove(deb_file)

        logger.success("QQ update completed")

        self.update_status = UpdateStatus.COMPLETED

    @staticmethod
    async def remove(purge: bool = False) -> None:
        logger.info("Remove QQ")

        await (await run_command("apt-get remove -y -qq linuxqq")).wait()
        shutil.rmtree(settings.qq.dir.base, ignore_errors=True)

        if purge:
            shutil.rmtree(settings.qq.dir.config, ignore_errors=True)


class NapCatManager(LogManager):
    service_path = "/etc/systemd/system/napcat.service"
    loader_path = os.path.join(settings.qq.dir.base, "resources/app/loadNapCat.js")
    env_path = os.path.join(settings.napcat.dir.base, "env")
    package_json_path = os.path.join(settings.napcat.dir.base, "package.json")

    napcat_config = {
        "fileLog": True,
        "consoleLog": False,
        "fileLogLevel": "debug" if status.debug else "warn",
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
        super().__init__(settings.napcat.dir.logs)

    def installed(self) -> bool:
        return os.path.isfile(self.package_json_path)

    @staticmethod
    def configured() -> bool:
        return settings.napcat.account >= 10000

    @staticmethod
    async def running() -> bool:
        return (await (await run_command("systemctl is-active --quiet napcat")).wait()) == 0

    async def get_version(self) -> str | None:
        if not self.installed():
            return None

        async with aiofiles.open(self.package_json_path, "r", encoding="utf-8") as f:
            try:
                data = json.loads(await f.read())
                return data.get("version")
            except ValueError:
                return None

    @staticmethod
    def get_log_file() -> str | None:
        if not os.path.isdir(settings.napcat.dir.logs) or not (files := os.listdir(settings.napcat.dir.logs)):
            return None

        for file in files:
            if os.path.isfile(os.path.join(settings.napcat.dir.logs, file)):
                return file

        return None

    async def update(self) -> None:
        logger.info("Check latest version of NapCat")

        self.update_status = UpdateStatus.CHECKING
        latest_version = (await get_versions()).data.napcat

        logger.info(f"Download NapCat, version: {latest_version}")

        self.update_status = UpdateStatus.DOWNLOADING
        url = f"{settings.cloud.download.base_url}/napcat/napcat-{latest_version}.zip"
        zip_file = f"{settings.app.dir.temp}/napcat-{latest_version}.zip"

        try:
            async with client.stream("GET", url) as response:
                async with aiofiles.open(zip_file, "wb") as f:
                    async for chunk in response.aiter_bytes(chunk_size=8192):
                        await f.write(chunk)
        except:
            logger.exception("Failed to download")
            self.update_status = UpdateStatus.FAILED
            return

        logger.info(f"Extract files")

        self.update_status = UpdateStatus.INSTALLING

        with zipfile.ZipFile(zip_file, "r") as z:
            z.extractall(settings.napcat.dir.base)

        os.remove(zip_file)

        # Configure systemd
        async with aiofiles.open(self.service_path, "w") as f:
            await f.write(f"""[Unit]
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
        async with aiofiles.open(self.loader_path, "w") as f:
            await f.write(f"(async () => {{await import(\"file:///{settings.napcat.dir.base}/napcat.mjs\");}})();")

        async with aiofiles.open(QQManager.package_json_path, "r+") as f:
            data = json.loads(await f.read())
            data["main"] = "./loadNapCat.js"
            await f.seek(0)
            await f.write(json.dumps(data, indent=2))
            await f.truncate()

        logger.success("NapCat update completed")

        self.update_status = UpdateStatus.COMPLETED

    async def remove(self) -> None:
        logger.info("Remove NapCat")

        if os.path.isfile(self.service_path):
            os.remove(self.service_path)
            await (await run_command("systemctl daemon-reload")).wait()

        shutil.rmtree(settings.napcat.dir.base, ignore_errors=True)

        logger.info("NapCat removed")

    async def start(self) -> None:
        logger.info("Start NapCat")

        if os.path.isdir(settings.napcat.dir.logs):
            shutil.rmtree(settings.napcat.dir.logs)

        async with aiofiles.open(self.env_path, "w") as f:
            await f.write(f"QQ_ACCOUNT={settings.napcat.account}")

        async with aiofiles.open(os.path.join(settings.napcat.dir.config, "napcat.json"), "w") as f:
            await f.write(json.dumps(self.napcat_config))

        async with aiofiles.open(os.path.join(settings.napcat.dir.config, f"napcat_{settings.napcat.account}.json"), "w") as f:
            await f.write(json.dumps(self.napcat_config))

        self.onebot_config["network"]["httpServers"][0]["host"] = str(settings.napcat.api.host)
        self.onebot_config["network"]["httpServers"][0]["port"] = settings.napcat.api.port
        self.onebot_config["network"]["httpClients"][0]["token"] = settings.security.webhook.secret

        async with aiofiles.open(os.path.join(settings.napcat.dir.config, f"onebot11_{settings.napcat.account}.json"), "w") as f:
            await f.write(json.dumps(self.onebot_config))

        await (await run_command("systemctl start napcat")).wait()

        logger.info("NapCat started")

    @staticmethod
    async def stop() -> None:
        logger.info("Stop NapCat")

        await (await run_command("systemctl stop napcat")).wait()

        logger.info("NapCat stopped")

    async def clean(self):
        logger.debug("Clean NapCat manager")

        await super().clean()


dicerobot_manager = DiceRobotManager()
qq_manager = QQManager()
napcat_manager = NapCatManager()


async def init_manager() -> None:
    dicerobot_manager.ensure_directory()

    if all([
        settings.napcat.autostart,
        qq_manager.installed(),
        napcat_manager.installed(),
        napcat_manager.configured(),
        not await napcat_manager.running()
    ]):
        logger.info("Automatically start NapCat")

        await napcat_manager.start()

    logger.debug("Manager initialized")


async def clean_manager() -> None:
    logger.debug("Clean manager")

    await dicerobot_manager.clean()
    await napcat_manager.clean()
