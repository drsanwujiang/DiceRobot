from . import BaseModel


class AuthRequest(BaseModel):
    password: str


class SetModuleStatusRequest(BaseModel):
    order: bool
    event: bool


class UpdateSettingsRequest(BaseModel):
    class Security(BaseModel):
        class Webhook(BaseModel):
            token: str

        class JWT(BaseModel):
            secret: str
            algorithm: str

        class Admin(BaseModel):
            password: str

        webhook: Webhook
        jwt: JWT
        admin: Admin

    class Mirai(BaseModel):
        class API(BaseModel):
            base_url: str

        api: API

    security: Security
    mirai: Mirai
