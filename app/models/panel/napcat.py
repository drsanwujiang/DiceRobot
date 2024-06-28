from ipaddress import IPv4Address

from pydantic import Field

from ...models import BaseModel

__all__ = [
    "UpdateNapCatSettingsRequest"
]


class UpdateNapCatSettingsRequest(BaseModel):
    class API(BaseModel):
        host: IPv4Address = None
        port: int = Field(None, gt=0)

    api: API = None
    account: int = Field(None, gt=10000)
