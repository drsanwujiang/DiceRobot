import os
import subprocess
import zipfile
import shutil
import json
import uuid

from .log import logger
from .config import settings


class QQManager:
    qq_dir = "/opt/QQ"
    qq_path = os.path.join(qq_dir, "qq")
    package_json_path = os.path.join(qq_dir, "resources/app/package.json")
    index_path = os.path.join(qq_dir, "resources/app/app_launcher/index.js")

    def __init__(self):
        self.deb_file: str | None = None
        self.download_process: subprocess.Popen | None = None
        self.install_process: subprocess.Popen | None = None

    def is_downloading(self) -> bool:
        return self.download_process is not None and self.download_process.poll() is None

    def is_downloaded(self) -> bool:
        return self.deb_file is not None and os.path.isfile(self.deb_file)

    def is_installing(self) -> bool:
        return self.install_process is not None and self.install_process.poll() is None

    def is_installed(self) -> bool:
        return os.path.isfile(self.qq_path)

    def get_version(self) -> str | None:
        if not os.path.isfile(self.package_json_path):
            return None

        with open(self.package_json_path, "r", encoding="utf-8") as f:
            try:
                data = json.load(f)
                return data.get("version")
            except ValueError:
                return None

    def download(self) -> None:
        if self.is_downloading() or self.is_downloaded():
            return

        logger.info("Download QQ")

        self.deb_file = f"/tmp/qq-{uuid.uuid4().hex}.deb"
        self.download_process = subprocess.Popen(
            f"curl -s -o {self.deb_file} https://dl.drsanwujiang.com/dicerobot/qq.deb",
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
            shell=True
        )

        logger.info("QQ downloaded")

    def install(self) -> None:
        if self.is_installed() or self.is_installing() or not self.is_downloaded():
            return

        logger.info("Install QQ")

        self.install_process = subprocess.Popen(
            f"apt-get install -y -qq {self.deb_file}",
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
            shell=True
        )

    def stop(self) -> None:
        if self.is_downloading():
            try:
                self.download_process.terminate()
                self.download_process.wait(5)
            except subprocess.TimeoutExpired:
                self.download_process.kill()

        if self.is_installing():
            try:
                self.install_process.terminate()
                self.install_process.wait(5)
            except subprocess.TimeoutExpired:
                self.install_process.kill()

        self.install_process = None


class NapCatManager:
    service_path = "/etc/systemd/system/napcat.service"
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
        "consoleLogLevel": "error"
    }
    onebot_config = {
        "http": {
            "enable": True,
            "host": str(settings.napcat.api.host),
            "port": settings.napcat.api.port,
            "secret": settings.security.webhook.secret,
            "enableHeart": False,
            "enablePost": True,
            "postUrls": [
                "http://127.0.0.1:9500/report"
            ]
        },
        "ws": {
            "enable": False
        },
        "reverseWs": {
            "enable": False
        },
        "GroupLocalTime": {
            "Record": False,
            "RecordList": []
        },
        "debug": False,
        "messagePostFormat": "array",
        "enableLocalFile2Url": True,
        "reportSelfMessage": False
    }

    def __init__(self):
        self.zip_file: str | None = None
        self.download_process: subprocess.Popen | None = None

    def is_downloading(self) -> bool:
        return self.download_process is not None and self.download_process.poll() is None

    def is_downloaded(self) -> bool:
        return self.zip_file is not None and os.path.isfile(self.zip_file)

    def is_installed(self) -> bool:
        return os.path.isfile(self.package_json_path)

    @staticmethod
    def is_configured() -> bool:
        return settings.napcat.account >= 10000

    @staticmethod
    def is_running() -> bool:
        return subprocess.run("systemctl is-active --quiet napcat", shell=True).returncode == 0

    def get_version(self) -> str | None:
        if not self.is_installed():
            return None

        with open(self.package_json_path, "r", encoding="utf-8") as f:
            try:
                data = json.load(f)
                return data.get("version")
            except ValueError:
                return None

    def download(self) -> None:
        if self.is_downloading() or self.is_downloaded():
            return

        logger.info("Download NapCat")

        self.zip_file = f"/tmp/napcat-{uuid.uuid4().hex}.zip"
        self.download_process = subprocess.Popen(
            f"curl -s -o {self.zip_file} https://dl.drsanwujiang.com/dicerobot/napcat.zip",
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
            shell=True
        )

        logger.info("NapCat downloaded")

    def install(self) -> None:
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
ExecStart=/usr/bin/xvfb-run -a {QQManager.qq_path} --no-sandbox -q $QQ_ACCOUNT

[Install]
WantedBy=multi-user.target""")

        subprocess.run("systemctl daemon-reload", shell=True)

        # Patch QQ
        with open(QQManager.index_path, "w") as f:
            f.write("""const path = require('path');
const CurrentPath = path.dirname(__filename)
const hasNapcatParam = process.argv.includes('--no-sandbox');

if (hasNapcatParam) {
    (async () => {
        await import("file://" + path.join(CurrentPath, './napcat/napcat.mjs'));
    })();
} else {
    require('./launcher.node').load('external_index', module);
}""")

        logger.info("NapCat installed")

    def remove(self) -> None:
        if not self.is_installed() or self.is_running():
            return

        logger.info("Remove NapCat")

        if os.path.isfile(self.service_path):
            os.remove(self.service_path)
            subprocess.run("systemctl daemon-reload", shell=True)

        shutil.rmtree(self.napcat_dir)

        logger.info("NapCat removed")

    def start(self) -> None:
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

        self.onebot_config["http"]["host"] = str(settings.napcat.api.host)
        self.onebot_config["http"]["port"] = settings.napcat.api.port
        self.onebot_config["http"]["secret"] = settings.security.webhook.secret

        with open(os.path.join(self.config_dir, "onebot11.json"), "w") as f:
            json.dump(self.onebot_config, f)

        with open(os.path.join(self.config_dir, f"onebot11_{settings.napcat.account}.json"), "w") as f:
            json.dump(self.onebot_config, f)

        subprocess.run("systemctl start napcat", shell=True)

        logger.info("NapCat started")

    @classmethod
    def stop(cls) -> None:
        if not cls.is_running():
            return

        logger.info("Stop NapCat")

        subprocess.run("systemctl stop napcat", shell=True)

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


def init_manager() -> None:
    if all([
        settings.app.start_napcat_at_startup,
        qq_manager.is_installed(),
        napcat_manager.is_installed(),
        napcat_manager.is_configured(),
        not napcat_manager.is_running()
    ]):
        logger.info("Automatically start NapCat")

        napcat_manager.start()

    logger.info("Manager initialized")


def clean_manager() -> None:
    logger.info("Clean manager")

    qq_manager.stop()
