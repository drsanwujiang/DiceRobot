from typing import Any

from pydantic import BaseModel as _BaseModel, ConfigDict
from pydantic.alias_generators import to_camel

from ..enum import MessageChainType, EventType


class BaseModel(_BaseModel):
    def model_dump(self, **kwargs) -> dict[str, Any]:
        return super().model_dump(serialize_as_any=True, **kwargs)

    def model_dump_json(self, **kwargs) -> str:
        return super().model_dump_json(serialize_as_any=True, **kwargs)


class CamelizableModel(BaseModel):
    model_config = ConfigDict(
        alias_generator=to_camel,
        populate_by_name=True,
        loc_by_alias=False
    )


class UserProfile(CamelizableModel):
    nickname: str
    email: str
    age: int
    level: int
    sign: str
    sex: str


class GroupMemberProfile(CamelizableModel):
    class Group(CamelizableModel):
        id: int
        name: str
        permission: str  # Bot's permissions in the group

    id: int
    member_name: str
    permission: str
    special_title: str
    join_timestamp: int
    last_speak_timestamp: int
    mute_time_remaining: int
    group: Group


class MessageChainOrEvent(CamelizableModel):
    type: MessageChainType | EventType
