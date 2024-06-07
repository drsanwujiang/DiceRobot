from typing import Any, Literal, Self

from pydantic import field_validator, model_validator

from ..enum import MessageType, MessageChainType
from . import CamelizableModel, GroupMemberProfile, MessageChainOrEvent

__all__ = [
    "Message",
    "Source",
    "Quote",
    "At",
    "Plain",
    "Image",
    "MessageChain",
    "FriendMessage",
    "GroupMessage",
    "TempMessage"
]


class Message(CamelizableModel):
    type: MessageType


class Source(Message):
    type: Literal[MessageType.SOURCE] = MessageType.SOURCE
    id: int
    time: int


class Quote(Message):
    type: Literal[MessageType.QUOTE] = MessageType.QUOTE
    group_id: int
    sender_id: int
    target_id: int
    origin: list[Message]


class At(Message):
    type: Literal[MessageType.AT] = MessageType.AT
    target: int
    display: str


class Plain(Message):
    type: Literal[MessageType.PLAIN] = MessageType.PLAIN
    text: str


class Image(Message):
    type: Literal[MessageType.IMAGE] = MessageType.IMAGE
    image_id: str | None = None
    url: str | None = None
    path: str | None = None
    base64: str | None = None

    @model_validator(mode="after")
    def check_image(self) -> Self:
        if not any([self.image_id, self.url, self.path, self.base64]):
            raise ValueError("No image data provided")

        return self


class MessageChain(MessageChainOrEvent):
    type: MessageChainType
    sender: Any
    message_chain: list[Message]

    @classmethod
    @field_validator("message_chain", mode="before")
    def validate_message_chain(cls, messages: list[dict]) -> list[Message]:
        parsed_messages: list[Message] = []

        for message in messages:
            _message = Message.model_validate(message)
            parsed_messages.append(globals()[_message.type.value].model_validate(message))

        return parsed_messages


class FriendMessage(MessageChain):
    class Sender(CamelizableModel):
        id: int
        nickname: str
        remark: str

    type: Literal[MessageChainType.FRIEND] = MessageChainType.FRIEND
    sender: Sender


class GroupMessage(MessageChain):
    class Sender(GroupMemberProfile):
        pass

    type: Literal[MessageChainType.GROUP] = MessageChainType.GROUP
    sender: Sender


class TempMessage(MessageChain):
    class Sender(GroupMemberProfile):
        pass

    type: Literal[MessageChainType.TEMP] = MessageChainType.TEMP
    sender: Sender
