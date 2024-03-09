import pydantic
from pydantic.alias_generators import to_camel


class BaseModel(pydantic.BaseModel):
    pass


class CamelizableModel(BaseModel):
    model_config = pydantic.ConfigDict(alias_generator=to_camel)


def init_internal() -> None:
    from .dispatcher import init_dispatcher

    init_dispatcher()


def clean_internal() -> None:
    pass
