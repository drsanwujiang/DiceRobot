from ...models import BaseModel

__all__ = [
    "RemoveQQRequest"
]


class RemoveQQRequest(BaseModel):
    purge: bool
