from typing import Any

from pydantic import BaseModel as BaseModel_

__all__ = [
    "BaseModel"
]


class BaseModel(BaseModel_):
    def model_dump(self, **kwargs) -> dict[str, Any]:
        return super().model_dump(serialize_as_any=True, **kwargs)

    def model_dump_json(self, **kwargs) -> str:
        return super().model_dump_json(serialize_as_any=True, **kwargs)
