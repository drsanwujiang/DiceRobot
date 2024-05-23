from typing import Any

from pydantic import field_validator

from . import CamelizableModel, MessageChainOrEvent

parsable_messages = [
    "Source", "Quote", "At", "Plain", "Image"
]


class Message(CamelizableModel):
    type: str


class Source(Message):
    type: str = "Source"
    id: int
    time: int


class Quote(Message):
    type: str = "Quote"
    group_id: int
    sender_id: int
    target_id: int
    origin: list[Message]


class At(Message):
    type: str = "At"
    target: int
    display: str


class Plain(Message):
    type: str = "Plain"
    text: str


class Image(Message):
    type: str = "Image"
    image_id: str | None = None
    url: str | None = None
    path: str | None = None
    base64: str | None = None


class MessageChain(MessageChainOrEvent):
    type: str
    sender: Any
    message_chain: list[Message]

    @field_validator("message_chain", mode="before")
    def validate_message_chain(cls, messages: list[dict]) -> list[Message]:
        parsed_messages: list[Message] = []

        for message in messages:
            try:
                if message["type"] in parsable_messages:
                    parsed_messages.append(globals()[message["type"]].model_validate(message))
                else:
                    raise ValueError("Unparsable message type: " + message["type"])
            except KeyError:
                raise ValueError("Message type not found")

        return parsed_messages


class FriendMessage(MessageChain):
    class Sender(CamelizableModel):
        id: int
        nickname: str
        remark: str

    type: str = "FriendMessage"
    sender: Sender


class GroupMessage(MessageChain):
    class Sender(CamelizableModel):
        class Group(CamelizableModel):
            id: int
            name: str
            permission: str

        id: int
        member_name: str
        special_title: str
        permission: str
        join_timestamp: int
        last_speak_timestamp: int
        mute_time_remaining: int
        group: Group

    type: str = "GroupMessage"
    sender: Sender


class TempMessage(GroupMessage):
    type: str = "TempMessage"
