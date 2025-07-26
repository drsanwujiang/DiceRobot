from . import Request

__all__ = [
    "AuthRequest",
    "SetModuleStatusRequest",
    "UpdateSecuritySettingsRequest",
    "UpdateApplicationSettingsRequest"
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


class UpdateApplicationSettingsRequest(Request):
    class Directory(Request):
        base: str = None
        logs: str = None
        temp: str = None

    dir: Directory = None
