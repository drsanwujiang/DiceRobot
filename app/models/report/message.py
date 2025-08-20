from typing import Any, Literal

from pydantic import field_validator

from ...enum import ReportType, MessageType, PrivateMessageSubType, GroupMessageSubType, SegmentType, Sex, Role
from .. import BaseModel
from . import Report
from .segment import Segment, Text, Image, At

__all__ = [
    "Message",
    "PrivateMessage",
    "GroupMessage"
]


class Message(Report):
    post_type: Literal[ReportType.MESSAGE] = ReportType.MESSAGE
    message_type: MessageType
    message_id: int
    user_id: int
    group_id: int | None = None
    message: list[Segment]
    raw_message: str
    font: int
    sender: Any

    @field_validator("message", mode="before")
    def parse_message(cls, segments: list[dict]) -> list[Segment]:
        parsed_segments: list[Segment] = []

        for segment in segments:
            if "type" not in segment:
                raise ValueError

            match segment["type"]:
                case SegmentType.TEXT.value:
                    parsed_segments.append(Text.model_validate(segment))
                case SegmentType.IMAGE.value:
                    parsed_segments.append(Image.model_validate(segment))
                case SegmentType.AT.value:
                    parsed_segments.append(At.model_validate(segment))
                case _:
                    raise ValueError

        return parsed_segments

    @property
    def from_group(self) -> bool:
        return self.message_type == MessageType.GROUP

    @property
    def from_friend(self) -> bool:
        return False

    @property
    def from_group_temp(self) -> bool:
        return False


class PrivateMessage(Message):
    class Sender(BaseModel):
        user_id: int
        nickname: str
        sex: Sex | None = None
        age: int | None = None
        card: str | None = None

    message_type: Literal[MessageType.PRIVATE] = MessageType.PRIVATE
    sub_type: PrivateMessageSubType
    sender: Sender

    def from_friend(self) -> bool:
        return self.sub_type and self.sub_type == PrivateMessageSubType.FRIEND

    @property
    def from_group_temp(self) -> bool:
        return self.sub_type == PrivateMessageSubType.GROUP


class GroupMessage(Message):
    class Anonymous(BaseModel):
        id: int
        name: str
        flag: str

    class Sender(BaseModel):
        user_id: int
        nickname: str
        card: str | None = None
        sex: Sex | None = None
        age: int | None = None
        area: str | None = None
        level: str | None = None
        role: Role | None = None  # In rare cases, the sender of a group message may not have a role field
        title: str | None = None

    message_type: Literal[MessageType.GROUP] = MessageType.GROUP
    sub_type: GroupMessageSubType
    group_id: int
    anonymous: Anonymous | None = None
    sender: Sender
