from typing import Any

from pydantic import BaseModel as _BaseModel, ConfigDict
from pydantic.alias_generators import to_camel


class BaseModel(_BaseModel):
    def model_dump(self, **kwargs) -> dict[str, Any]:
        return super().model_dump(serialize_as_any=True, **kwargs)

    def model_dump_json(self, **kwargs) -> str:
        return super().model_dump_json(serialize_as_any=True, **kwargs)


class CamelizableModel(BaseModel):
    model_config = ConfigDict(alias_generator=to_camel)


class MessageChainOrEvent(CamelizableModel):
    type: str


def init_internal() -> None:
    from .dispatcher import init_dispatcher

    init_dispatcher()


def clean_internal() -> None:
    pass
