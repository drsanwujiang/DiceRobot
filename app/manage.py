import os
import subprocess
import zipfile
import shutil
import json

from .log import logger
from .config import settings
from .network.dicerobot import download_qq, download_napcat


class QQManager:
    qq_path = "/opt/QQ/qq"
    package_json_path = "/opt/QQ/resources/app/package.json"

    def __init__(self):
        self.deb_file: str | None = None
        self.install_process: subprocess.Popen | None = None

    def is_downloaded(self) -> bool:
        return self.deb_file is not None and os.path.isfile(self.deb_file)

    def is_installing(self) -> bool:
        return self.install_process is not None and self.install_process.poll() is None

    @classmethod
    def is_installed(cls) -> bool:
        return os.path.isfile(cls.qq_path)

    @classmethod
    def get_version(cls) -> str | None:
        if not os.path.isfile(cls.package_json_path):
            return None

        with open(cls.package_json_path, "r", encoding="utf-8") as f:
            try:
                data = json.load(f)
                return data.get("version")
            except ValueError:
                return None

    def download(self) -> None:
        if self.is_downloaded():
            return

        logger.info("Download QQ")

        self.deb_file = None
        self.deb_file = download_qq()

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
        if self.is_installing():
            try:
                self.install_process.terminate()
                self.install_process.wait(5)
            except subprocess.TimeoutExpired:
                self.install_process.kill()

        self.install_process = None


class NapCatManager:
    service_path = "/etc/systemd/system/napcat.service"
    napcat_dir = os.path.join(os.getcwd(), "napcat")
    log_dir = os.path.join(napcat_dir, "logs")
    config_dir = os.path.join(napcat_dir, "config")
    napcat_file = "napcat.sh"
    napcat_path = os.path.join(napcat_dir, napcat_file)
    env_file = "env"
    env_path = os.path.join(napcat_dir, env_file)
    package_json_file = "package.json"
    package_json_path = os.path.join(napcat_dir, package_json_file)

    napcat_config = {
        "fileLog": True,
        "consoleLog": False,
        "fileLogLevel": "warn",
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
        self.process: subprocess.Popen | None = None

    @classmethod
    def is_installed(cls) -> bool:
        return os.path.isfile(cls.napcat_path)

    @staticmethod
    def is_configured() -> bool:
        return settings.napcat.account >= 10000

    @staticmethod
    def is_running() -> bool:
        return subprocess.run("systemctl is-active --quiet napcat", shell=True).returncode == 0

    @classmethod
    def get_version(cls) -> str | None:
        if not os.path.isfile(cls.package_json_path):
            return None

        with open(cls.package_json_path, "r", encoding="utf-8") as f:
            try:
                data = json.load(f)
                return data.get("version")
            except ValueError:
                return None

    @classmethod
    def install(cls) -> None:
        if cls.is_installed():
            return

        logger.info("Install NapCat")

        file = download_napcat()

        with zipfile.ZipFile(file, "r") as z:
            z.extractall(cls.napcat_dir)

        os.remove(file)

        with open(cls.service_path, "w") as f:
            f.write(f"""[Unit]
Description=NapCat service created by DiceRobot
After=network.target

[Service]
Type=simple
User=root
EnvironmentFile={cls.env_path}
WorkingDirectory={cls.napcat_dir}
ExecStart=/bin/bash {cls.napcat_file} -q $QQ_ACCOUNT

[Install]
WantedBy=multi-user.target""")

        subprocess.run("systemctl daemon-reload", shell=True)

        logger.info("NapCat installed")

    @classmethod
    def remove(cls) -> None:
        if not cls.is_installed() or cls.is_running():
            return

        logger.info("Remove NapCat")

        if os.path.isfile(cls.service_path):
            os.remove(cls.service_path)
            subprocess.run("systemctl daemon-reload", shell=True)

        shutil.rmtree(cls.napcat_dir)

        logger.info("NapCat removed")

    @classmethod
    def start(cls) -> None:
        if not cls.is_installed() or not cls.is_configured() or cls.is_running():
            return

        logger.info("Start NapCat")

        if os.path.isdir(cls.log_dir):
            shutil.rmtree(cls.log_dir)

        with open(cls.env_path, "w") as f:
            f.write(f"QQ_ACCOUNT={settings.napcat.account}")

        with open(os.path.join(cls.config_dir, "napcat.json"), "w") as f:
            json.dump(cls.napcat_config, f)

        with open(os.path.join(cls.config_dir, f"napcat_{settings.napcat.account}.json"), "w") as f:
            json.dump(cls.napcat_config, f)

        with open(os.path.join(cls.config_dir, "onebot11.json"), "w") as f:
            json.dump(cls.onebot_config, f)

        with open(os.path.join(cls.config_dir, f"onebot11_{settings.napcat.account}.json"), "w") as f:
            json.dump(cls.onebot_config, f)

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
