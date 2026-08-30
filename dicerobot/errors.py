"""异常层次。

三类异常的处置方式不同：:class:`ConfigurationError` 属启动期错误，应让进程退出；
:class:`QQError` 及其子类记录日志后放弃本次处理；:class:`CommandError` 的
``message`` 会原样回复给用户，措辞需面向玩家。
"""

from __future__ import annotations

__all__ = [
    "ApiError",
    "CommandError",
    "ConfigurationError",
    "DiceRobotError",
    "QQError",
    "ReplyError",
    "ReplyQuotaExhaustedError",
    "ReplyWindowExpiredError",
    "SignatureError",
    "TokenError",
]


class DiceRobotError(Exception):
    """所有自定义异常的基类。"""


class ConfigurationError(DiceRobotError):
    """配置缺失或非法。"""


class QQError(DiceRobotError):
    """与 QQ 开放平台交互失败。"""


class SignatureError(QQError):
    """Webhook 请求的 Ed25519 签名校验未通过。

    响应应为 401 且不透露失败原因，请求方未必是平台本身。
    """


class TokenError(QQError):
    """获取或刷新 access token 失败。"""


class ApiError(QQError):
    """平台 OpenAPI 返回业务错误码。

    Attributes:
        code: 平台返回的错误码。
        message: 平台返回的错误描述。
        status_code: HTTP 状态码，网络层失败时为 0。
    """

    def __init__(self, code: int, message: str, status_code: int) -> None:
        super().__init__(f"QQ OpenAPI 错误 {code}（HTTP {status_code}）：{message}")

        self.code = code
        self.message = message
        self.status_code = status_code


class ReplyError(QQError):
    """被动回复无法送达。"""


class ReplyQuotaExhaustedError(ReplyError):
    """被动回复条数已用尽。群聊 5 条，单聊 4 条。"""


class ReplyWindowExpiredError(ReplyError):
    """已超出被动回复时间窗口。群聊 5 分钟，单聊 60 分钟。"""


class CommandError(DiceRobotError):
    """指令执行失败且需告知用户。``message`` 会原样回复到聊天中。"""

    def __init__(self, message: str) -> None:
        super().__init__(message)

        self.message = message
