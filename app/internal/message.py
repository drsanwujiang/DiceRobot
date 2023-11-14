from . import CamelizableModel


class Message(CamelizableModel):
    pass


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


class MessageChain(CamelizableModel):
    pass


class FriendMessage(MessageChain):
    class Sender(CamelizableModel):
        id: int
        nickname: str
        remark: str

    type: str
    sender: Sender
    message_chain: list[Message]


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

    type: str
    sender: Sender
    message_chain: list[Message]


class TempMessage(GroupMessage):
    pass
