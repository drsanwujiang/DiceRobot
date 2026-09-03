"""应用配置。

配置来自环境变量或 ``.env``，不入库。嵌套字段以双下划线分隔::

    QQ__APP_ID=102...
    QQ__SECRET=xxxx
"""

from __future__ import annotations

from functools import lru_cache
from pathlib import Path
from typing import Literal

from pydantic import BaseModel, ConfigDict, Field, SecretStr
from pydantic_settings import BaseSettings, SettingsConfigDict

__all__ = ["DEFAULT_DATABASE_URL", "BotSettings", "LogSettings", "QQSettings", "Settings", "get_settings"]


class _Section(BaseModel):
    """配置分节的基类。

    拒绝未知字段：pydantic 默认忽略多余的键，字段名拼错时会静默回退到默认值。
    """

    model_config = ConfigDict(extra="forbid")


# 数据库地址的默认值。迁移脚本亦引用此常量，避免出现两份来源。
DEFAULT_DATABASE_URL = "sqlite+aiosqlite:///data/dicerobot.db"


class QQSettings(_Section):
    """开放平台接入凭据。"""

    app_id: str = Field(min_length=1, description="AppID，同时用作 X-Union-Appid 请求头")
    secret: SecretStr = Field(description="AppSecret，用于获取 access token 及派生 Ed25519 签名密钥")
    request_timeout: float = Field(default=10.0, gt=0, description="调用 OpenAPI 的超时时间")
    keepalive_expiry: float = Field(default=300.0, gt=0, description="出站连接的空闲存活时间")


class BotSettings(_Section):
    """机器人行为配置。"""

    queue_size: int = Field(default=1000, gt=0, description="事件队列容量，队列满时丢弃并告警")
    workers: int = Field(default=32, gt=0, description="消费事件的 worker 数量")
    handler_timeout: float = Field(default=30.0, gt=0, description="单条指令的执行超时时间")


class LogSettings(_Section):
    """日志配置。"""

    level: Literal["TRACE", "DEBUG", "INFO", "SUCCESS", "WARNING", "ERROR", "CRITICAL"] = "INFO"
    directory: Path = Field(default=Path("logs"), description="日志文件目录")
    retention: str = Field(default="30 days", description="日志保留时长，由 loguru 解析")
    rotation: str = Field(default="00:00", description="日志切分策略，由 loguru 解析")
    serialize: bool = Field(default=False, description="是否以 JSON 行格式写入文件")


class Settings(BaseSettings):
    """应用的全部配置。"""

    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        env_nested_delimiter="__",
        extra="ignore",
    )

    debug: bool = Field(default=False, description="调试模式，开启后指令异常向上抛出而非被捕获")
    host: str = Field(default="127.0.0.1", description="监听地址，默认仅监听本机并由反向代理转发")
    port: int = Field(default=8080, gt=0, lt=65536, description="监听端口")
    database_url: str = Field(default=DEFAULT_DATABASE_URL)
    rules_directory: Path = Field(default=Path("data/rules"), description="检定规则文件所在目录")
    webhook_path: str = Field(default="/qq/webhook", description="Webhook 回调路径，需与平台配置一致")

    qq: QQSettings
    bot: BotSettings = BotSettings()
    log: LogSettings = LogSettings()


@lru_cache(maxsize=1)
def get_settings() -> Settings:
    """读取配置。结果带缓存，测试可调用 ``get_settings.cache_clear()`` 重置。"""

    return Settings()  # type: ignore[call-arg]  # 字段由环境变量填充
