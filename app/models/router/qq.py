from . import Request

__all__ = [
    "RemoveQQRequest",
    "UpdateQQSettingsRequest"
]


class RemoveQQRequest(Request):
    purge: bool


class UpdateQQSettingsRequest(Request):
    class Directory(Request):
        base: str = None
        config: str = None

    dir: Directory = None
