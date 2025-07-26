from pydantic import ConfigDict

from .. import BaseModel

__all__ = [
    "Request"
]


class Request(BaseModel):
    model_config = ConfigDict(extra="forbid")
