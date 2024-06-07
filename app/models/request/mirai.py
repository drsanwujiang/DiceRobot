from typing import Literal

from ...models import BaseModel, CamelizableModel

__all__ = [
    "UpdateAutologinConfigRequest",
    "CommandRequest"
]

class UpdateAutologinConfigRequest(CamelizableModel):
    class Account(CamelizableModel):
        class Password(CamelizableModel):
            kind: Literal["PLAIN", "MD5"]
            value: str

        class Configuration(CamelizableModel):
            protocol: Literal["ANDROID_PHONE", "ANDROID_PAD", "ANDROID_WATCH", "MACOS", "IPAD"]
            device: Literal["device.json"]
            enable: bool
            heartbeat_strategy: Literal["STAT_HB", "REGISTER", "NONE"]

        account: int
        password: Password
        configuration: Configuration

    accounts: list[Account]


class CommandRequest(BaseModel):
    command: str
