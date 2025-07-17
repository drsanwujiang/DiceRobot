from ...models import BaseModel

__all__ = [
    "RemoveQQRequest",
    "UpdateQQSettingsRequest"
]


class RemoveQQRequest(BaseModel):
    purge: bool


class UpdateQQSettingsRequest(BaseModel):
    class Directory(BaseModel):
        base: str = None
        config: str = None

    dir: Directory = None
