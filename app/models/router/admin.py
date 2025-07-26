from typing import Self

from pydantic import ConfigDict, model_validator

from . import Request

__all__ = [
    "AuthRequest",
    "SetModuleStatusRequest",
    "UpdateSecuritySettingsRequest",
    "UpdateApplicationSettingsRequest",
    "UpdatePluginSettingsRequest"
]


class AuthRequest(Request):
    password: str


class SetModuleStatusRequest(Request):
    order: bool
    event: bool


class UpdateSecuritySettingsRequest(Request):
    class Webhook(Request):
        secret: str

    class JWT(Request):
        secret: str
        algorithm: str

    class Admin(Request):
        password: str

    webhook: Webhook = None
    jwt: JWT = None
    admin: Admin = None

    @model_validator(mode="after")
    def check_fields(self) -> Self:
        if all([self.webhook is None, self.jwt is None, self.admin is None]):
            raise ValueError

        return self


class UpdateApplicationSettingsRequest(Request):
    class Directory(Request):
        base: str
        logs: str
        temp: str

    dir: Directory


class UpdatePluginSettingsRequest(Request):
    model_config = ConfigDict(extra="allow")

    enabled: bool
