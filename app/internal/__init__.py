import importlib

import pydantic
from pydantic.alias_generators import to_camel


class BaseModel(pydantic.BaseModel):
    pass


class CamelizableModel(BaseModel):
    model_config = pydantic.ConfigDict(alias_generator=to_camel)


def init_internal():
    importlib.import_module(".dispatcher", __package__).init_dispatcher()
    importlib.import_module(".schedule", __package__).init_schedule()


def clean_internal():
    from .schedule import clean_schedule

    clean_schedule()
