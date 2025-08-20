from apscheduler import AsyncScheduler

from .globals import DEBUG
from .models.config import Status, Settings, PluginSettings, ChatSettings, Replies
from .network import HttpClient
from .managers.database import DatabaseManager
from .managers.config import ConfigManager
from .managers.data import DataManager
from .managers.dispatch import DispatchManager
from .managers.task import TaskManager
from .managers.network import NetworkManager
from .actuators.app import AppActuator
from .actuators.qq import QQActuator
from .actuators.napcat import NapCatActuator


class AppContext:
    def __init__(self) -> None:
        self.status = Status(debug=DEBUG)
        self.settings = Settings()
        self.plugin_settings = PluginSettings()
        self.chat_settings = ChatSettings()
        self.replies = Replies()
        self.http_client = HttpClient()
        self.scheduler: AsyncScheduler | None = None
        self.database_manager: DatabaseManager | None = None
        self.config_manager: ConfigManager | None = None
        self.data_manager: DataManager | None = None
        self.dispatch_manager: DispatchManager | None = None
        self.task_manager: TaskManager | None = None
        self.network_manager: NetworkManager | None = None
        self.app_actuator: AppActuator | None = None
        self.qq_actuator: QQActuator | None = None
        self.napcat_actuator: NapCatActuator | None = None
