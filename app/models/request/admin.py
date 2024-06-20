from ...models import BaseModel

__all__ = [
    "AuthRequest",
    "SetModuleStatusRequest",
    "UpdateSecuritySettingsRequest",
    "UpdateApplicationSettingsRequest",
    "UpdateMiraiSettingsRequest"
]


class AuthRequest(BaseModel):
    password: str


class SetModuleStatusRequest(BaseModel):
    order: bool
    event: bool


class UpdateSecuritySettingsRequest(BaseModel):
    class Webhook(BaseModel):
        token: str

    class JWT(BaseModel):
        secret: str
        algorithm: str

    class Admin(BaseModel):
        password: str

    webhook: Webhook = None
    jwt: JWT = None
    admin: Admin = None


class UpdateApplicationSettingsRequest(BaseModel):
    start_mirai_at_startup: bool
    check_bot_status_at_startup: bool


class UpdateMiraiSettingsRequest(BaseModel):
    class Directory(BaseModel):
        base: str
        logs: str
        config: str
        config_console: str
        config_api: str

    class File(BaseModel):
        mcl: str
        mcl_config: str
        config_autologin: str
        config_api: str

    class API(BaseModel):
        host: str
        port: int
        base_url: str

    dir: Directory
    file: File
    api: API
