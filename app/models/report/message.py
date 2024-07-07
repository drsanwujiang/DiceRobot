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
    message: list[Segment]
    raw_message: str
    font: int
    sender: Any

    @field_validator("message", mode="before")
    def validate_message(cls, segments: list[dict]) -> list[Segment]:
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


class PrivateMessage(Message):
    class Sender(BaseModel):
        user_id: int
        nickname: str
        sex: Sex = None
        age: int = None
        card: str = None

    message_type: Literal[MessageType.PRIVATE] = MessageType.PRIVATE
    sub_type: PrivateMessageSubType
    sender: Sender


class GroupMessage(Message):
    class Anonymous(BaseModel):
        id: int
        name: str
        flag: str

    class Sender(BaseModel):
        user_id: int
        nickname: str
        card: str = None
        sex: Sex = None
        age: int = None
        area: str = None
        level: str = None
        role: Role = None  # In rare cases, the sender of a group message may not have role field
        title: str = None

    message_type: Literal[MessageType.GROUP] = MessageType.GROUP
    sub_type: GroupMessageSubType
    group_id: int
    anonymous: Anonymous = None
    sender: Sender
