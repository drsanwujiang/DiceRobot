from ipaddress import IPv4Address

from pydantic import Field

from . import Request

__all__ = [
    "UpdateNapCatSettingsRequest"
]


class UpdateNapCatSettingsRequest(Request):
    class Directory(Request):
        base: str = None
        logs: str = None
        config: str = None

    class API(Request):
        host: IPv4Address = None
        port: int = Field(None, gt=0)

    dir: Directory = None
    api: API = None
    account: int = Field(None, gt=10000)
    autostart: bool = None
