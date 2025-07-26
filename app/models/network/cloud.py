from typing import Any

from .. import BaseModel

__all__ = [
    "GetVersionsResponse"
]


class CloudAPIResponse(BaseModel):
    code: int
    message: str
    data: Any


class GetVersionsResponse(CloudAPIResponse):
    class Data(BaseModel):
        dicerobot: str
        napcat: str
        qq: str

    data: Data
