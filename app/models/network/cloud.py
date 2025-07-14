from typing import Any

from .. import BaseModel

__all__ = [
    "GetVersionsResponse"
]


class CloudAPIResponse(BaseModel):
    code: int
    message: str
    data: Any


class GetVersionsResponse(BaseModel):
    class Data(BaseModel):
        dicerobot: str
        napcat: str
        qq: str

    data: Data
