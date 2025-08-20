from typing import Self
from ipaddress import IPv4Address

from pydantic import Field, model_validator

from . import Request

__all__ = [
    "UpdateNapCatSettingsRequest"
]


class UpdateNapCatSettingsRequest(Request):
    class Directory(Request):
        base: str
        config: str
        logs: str

    class API(Request):
        host: IPv4Address
        port: int = Field(gt=0)

    dir: Directory = None
    api: API = None
    account: int = Field(default=None, gt=10000)
    autostart: bool = None

    @model_validator(mode="after")
    def check_fields(self) -> Self:
        if all([self.dir is None, self.api is None, self.account is None, self.autostart is None]):
            raise ValueError

        return self
