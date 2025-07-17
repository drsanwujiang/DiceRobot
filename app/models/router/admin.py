from ...models import BaseModel

__all__ = [
    "AuthRequest",
    "SetModuleStatusRequest",
    "UpdateSecuritySettingsRequest",
    "UpdateApplicationSettingsRequest"
]


class AuthRequest(BaseModel):
    password: str


class SetModuleStatusRequest(BaseModel):
    order: bool
    event: bool


class UpdateSecuritySettingsRequest(BaseModel):
    class Webhook(BaseModel):
        secret: str

    class JWT(BaseModel):
        secret: str
        algorithm: str

    class Admin(BaseModel):
        password: str

    webhook: Webhook = None
    jwt: JWT = None
    admin: Admin = None


class UpdateApplicationSettingsRequest(BaseModel):
    class Directory(BaseModel):
        base: str = None
        logs: str = None
        temp: str = None

    dir: Directory = None
