from ipaddress import IPv4Address

from pydantic import Field

from ...models import BaseModel

__all__ = [
    "UpdateNapCatSettingsRequest"
]


class UpdateNapCatSettingsRequest(BaseModel):
    class Directory(BaseModel):
        base: str = None
        logs: str = None
        config: str = None

    class API(BaseModel):
        host: IPv4Address = None
        port: int = Field(None, gt=0)

    dir: Directory = None
    api: API = None
    account: int = Field(None, gt=10000)
    autostart: bool = None
