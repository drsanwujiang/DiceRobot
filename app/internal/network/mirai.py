from pydantic import Field, field_serializer

from ...config import settings
from .. import BaseModel, CamelizableModel
from ..message import Message
from . import client


class Request(BaseModel):
    pass


class Response(CamelizableModel):
    code: int
    msg: str


class GetBotListResponse(Response):
    data: list[int]


def get_bot_list() -> GetBotListResponse:
    return GetBotListResponse.model_validate(
        client.get(
            settings["mirai"]["api"]["base_url"] + "/botList"
        ).json()
    )


class GetBotProfileResponse(BaseModel):
    nickname: str
    email: str
    age: int
    level: int
    sign: str
    sex: str


def get_bot_profile() -> GetBotProfileResponse:
    return GetBotProfileResponse.model_validate(
        client.get(
            settings["mirai"]["api"]["base_url"] + "/botProfile"
        ).json()
    )


class GetFriendListResponse(Response):
    class Friend(BaseModel):
        id: int
        nickname: str
        remark: str

    data: list[Friend]


def get_friend_list() -> GetFriendListResponse:
    return GetFriendListResponse.model_validate(
        client.get(
            settings["mirai"]["api"]["base_url"] + "/friendList"
        ).json()
    )


class GetGroupListResponse(Response):
    class Group(BaseModel):
        id: int
        name: str
        permission: str

    data: list[Group]


def get_group_list() -> GetGroupListResponse:
    return GetGroupListResponse.model_validate(
        client.get(
            settings["mirai"]["api"]["base_url"] + "/groupList"
        ).json()
    )


class GetFriendProfileResponse(BaseModel):
    nickname: str
    email: str
    age: int
    level: int
    sign: str
    sex: str


def get_friend_profile(friend_id: int) -> GetFriendProfileResponse:
    return GetFriendProfileResponse.model_validate(
        client.get(
            settings["mirai"]["api"]["base_url"] + "/friendProfile",
            params={"target": friend_id}
        ).json()
    )


class GetGroupMemberProfileResponse(GetFriendProfileResponse):
    pass


def get_group_member_profile(group_id: int, member_id: int) -> GetGroupMemberProfileResponse:
    return GetGroupMemberProfileResponse.model_validate(
        client.get(
            settings["mirai"]["api"]["base_url"] + "/memberProfile",
            params={"target": group_id, "member": member_id}
        ).json()
    )


class SendFriendMessageRequest(Request):
    target: int
    message_chain: list[Message] = Field(serialization_alias="messageChain")

    @field_serializer("message_chain")
    def serialize_dt(self, message_chain: list[Message], _info):
        return [message.model_dump(by_alias=True) for message in message_chain]


class SendFriendMessageResponse(Response):
    message_id: int = Field(alias="messageId")


def send_friend_message(data: dict) -> SendFriendMessageResponse:
    return SendFriendMessageResponse.model_validate(
        client.post(
            settings["mirai"]["api"]["base_url"] + "/sendFriendMessage",
            json=SendFriendMessageRequest.model_validate(data).model_dump(by_alias=True)
        ).json()
    )


class SendGroupMessageRequest(SendFriendMessageRequest):
    pass


class SendGroupMessageResponse(SendFriendMessageResponse):
    pass


def send_group_message(data: dict) -> SendGroupMessageResponse:
    return SendGroupMessageResponse.model_validate(
        client.post(
            settings["mirai"]["api"]["base_url"] + "/sendGroupMessage",
            json=SendGroupMessageRequest.model_validate(data).model_dump(by_alias=True)
        ).json()
    )


class SendTempMessageRequest(Request):
    qq: int
    group: int
    message_chain: list[Message] = Field(serialization_alias="messageChain")

    @field_serializer("message_chain")
    def serialize_dt(self, message_chain: list[Message], _info):
        return [message.model_dump(by_alias=True) for message in message_chain]


class SendTempMessageResponse(SendGroupMessageResponse):
    pass


def send_temp_message(data: dict) -> SendTempMessageResponse:
    return SendTempMessageResponse.model_validate(
        client.post(
            settings["mirai"]["api"]["base_url"] + "/sendTempMessage",
            json=SendTempMessageRequest.model_validate(data).model_dump(by_alias=True)
        ).json()
    )


class RespondNewFriendRequestEventRequest(Request):
    event_id: int = Field(serialization_alias="eventId")
    from_id: int = Field(serialization_alias="fromId")
    group_id: int = Field(serialization_alias="groupId")
    operate: int
    message: str


class RespondNewFriendRequestEventResponse(Response):
    pass


def respond_new_friend_request_event(data: dict) -> RespondNewFriendRequestEventResponse:
    return RespondNewFriendRequestEventResponse.model_validate(
        client.post(
            settings["mirai"]["api"]["base_url"] + "/resp/newFriendRequestEvent",
            json=RespondNewFriendRequestEventRequest.model_validate(data).model_dump(by_alias=True)
        ).json()
    )


class RespondBotInvitedJoinGroupRequestEventRequest(RespondNewFriendRequestEventRequest):
    pass


class RespondBotInvitedJoinGroupRequestEventResponse(Response):
    pass


def respond_bot_invited_join_group_request_event(data: dict) -> RespondBotInvitedJoinGroupRequestEventResponse:
    return RespondBotInvitedJoinGroupRequestEventResponse.model_validate(
        client.post(
            settings["mirai"]["api"]["base_url"] + "/resp/botInvitedJoinGroupRequestEvent",
            json=RespondBotInvitedJoinGroupRequestEventRequest.model_validate(data).model_dump(by_alias=True)
        ).json()
    )
