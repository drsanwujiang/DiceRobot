from typing import Any

from pydantic import BaseModel as _BaseModel


class BaseModel(_BaseModel):
    def model_dump(self, **kwargs) -> dict[str, Any]:
        return super().model_dump(serialize_as_any=True, **kwargs)

    def model_dump_json(self, **kwargs) -> str:
        return super().model_dump_json(serialize_as_any=True, **kwargs)
