from ...models import BaseModel

__all__ = [
    "UpdateNapCatSettingsRequest"
]


class UpdateNapCatSettingsRequest(BaseModel):
    class API(BaseModel):
        host: str = None
        port: int = None
        base_url: str = None

    api: API = None
    account: int = None
