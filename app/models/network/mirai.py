from pydantic import field_serializer

from .. import CamelizableModel, UserProfile, GroupMemberProfile
from ..message import Message, MessageChain

__all__ = [
    "GetPluginInfoResponse",
    "GetBotListResponse",
    "GetFriendListResponse",
    "GetGroupListResponse",
    "GetGroupMemberListResponse",
    "GetBotProfileResponse",
    "GetFriendProfileResponse",
    "GetGroupMemberProfileResponse",
    "GetUserProfileResponse",
    "SendFriendMessageRequest",
    "SendFriendMessageResponse",
    "SendGroupMessageRequest",
    "SendGroupMessageResponse",
    "SendTempMessageRequest",
    "SendTempMessageResponse",
    "SendNudgeMessageRequest",
    "SendNudgeMessageResponse",
    "RecallMessageRequest",
    "RecallMessageResponse",
    "GetRoamingMessagesRequest",
    "GetRoamingMessagesResponse",
    "DeleteFriendRequest",
    "DeleteFriendResponse",
    "MuteGroupMemberRequest",
    "MuteGroupMemberResponse",
    "UnmuteGroupMemberRequest",
    "UnmuteGroupMemberResponse",
    "KickGroupMemberRequest",
    "KickGroupMemberResponse",
    "QuitGroupRequest",
    "QuitGroupResponse",
    "MuteAllRequest",
    "MuteAllResponse",
    "UnmuteAllRequest",
    "UnmuteAllResponse",
    "GetGroupMemberInfoResponse",
    "SetGroupMemberInfoRequest",
    "SetGroupMemberInfoResponse",
    "RespondNewFriendRequestEventRequest",
    "RespondNewFriendRequestEventResponse",
    "RespondMemberJoinRequestEventRequest",
    "RespondMemberJoinRequestEventResponse",
    "RespondBotInvitedJoinGroupRequestEventRequest",
    "RespondBotInvitedJoinGroupRequestEventResponse"
]


class MiraiAPIRequest(CamelizableModel):
    pass


class MiraiAPIResponse(CamelizableModel):
    pass


class StandardResponse(CamelizableModel):
    code: int
    msg: str


class SendMessageChainRequest(CamelizableModel):
    message_chain: list[Message]

    @field_serializer("message_chain")
    def serialize_message_chain(self, message_chain: list[Message], _):
        return [message.model_dump(by_alias=True) for message in message_chain]


class RespondEventRequest(CamelizableModel):
    event_id: int
    from_id: int
    group_id: int
    operate: int
    message: str


# Plugin info

class GetPluginInfoResponse(MiraiAPIResponse, StandardResponse):
    class Data(CamelizableModel):
        version: str

    data: Data


class GetBotListResponse(MiraiAPIResponse, StandardResponse):
    data: list[int]


# Account info

class GetFriendListResponse(MiraiAPIResponse, StandardResponse):
    class Friend(CamelizableModel):
        id: int
        nickname: str
        remark: str

    data: list[Friend]


class GetGroupListResponse(MiraiAPIResponse, StandardResponse):
    class Group(CamelizableModel):
        id: int
        name: str
        permission: str

    data: list[Group]


class GetGroupMemberListResponse(MiraiAPIResponse, StandardResponse):
    class GroupMember(GroupMemberProfile):
        pass

    data: list[GroupMember]


class GetBotProfileResponse(MiraiAPIResponse, UserProfile):
    pass


class GetFriendProfileResponse(MiraiAPIResponse, UserProfile):
    pass


class GetGroupMemberProfileResponse(MiraiAPIResponse, UserProfile):
    pass


class GetUserProfileResponse(MiraiAPIResponse, UserProfile):
    pass


# Message

class SendFriendMessageRequest(MiraiAPIRequest, SendMessageChainRequest):
    target: int


class SendFriendMessageResponse(MiraiAPIResponse, StandardResponse):
    message_id: int


class SendGroupMessageRequest(MiraiAPIRequest, SendMessageChainRequest):
    target: int


class SendGroupMessageResponse(MiraiAPIResponse, StandardResponse):
    message_id: int


class SendTempMessageRequest(MiraiAPIRequest, SendMessageChainRequest):
    qq: int
    group: int


class SendTempMessageResponse(MiraiAPIResponse, StandardResponse):
    message_id: int


class SendNudgeMessageRequest(MiraiAPIRequest):
    target: int
    subject: int
    kind: str


class SendNudgeMessageResponse(MiraiAPIResponse, StandardResponse):
    message_id: int


class RecallMessageRequest(MiraiAPIRequest):
    target: int
    message_id: int


class RecallMessageResponse(MiraiAPIResponse, StandardResponse):
    pass


class GetRoamingMessagesRequest(MiraiAPIRequest):
    time_start: int
    time_end: int
    target: int


class GetRoamingMessagesResponse(MiraiAPIResponse, StandardResponse):
    data: list[MessageChain]


# Account management

class DeleteFriendRequest(MiraiAPIRequest):
    target: int


class DeleteFriendResponse(MiraiAPIResponse, StandardResponse):
    pass


# Group management

class MuteGroupMemberRequest(MiraiAPIRequest):
    target: int
    member_id: int
    time: int = 0


class MuteGroupMemberResponse(MiraiAPIResponse, StandardResponse):
    pass


class UnmuteGroupMemberRequest(MiraiAPIRequest):
    target: int
    member_id: int


class UnmuteGroupMemberResponse(MiraiAPIResponse, StandardResponse):
    pass


class KickGroupMemberRequest(MiraiAPIRequest):
    target: int
    member_id: int
    block: bool = False
    msg: str = ""


class KickGroupMemberResponse(MiraiAPIResponse, StandardResponse):
    pass


class QuitGroupRequest(MiraiAPIRequest):
    target: int


class QuitGroupResponse(MiraiAPIResponse, StandardResponse):
    pass


class MuteAllRequest(MiraiAPIRequest):
    target: int


class MuteAllResponse(MiraiAPIResponse, StandardResponse):
    pass


class UnmuteAllRequest(MiraiAPIRequest):
    target: int


class UnmuteAllResponse(MiraiAPIResponse, StandardResponse):
    pass


class GetGroupMemberInfoResponse(MiraiAPIRequest, GroupMemberProfile):
    class Active(CamelizableModel):
        rank: int
        point: int
        honors: list[str]
        temperature: int

    active: Active


class SetGroupMemberInfoRequest(MiraiAPIRequest):
    class Info(CamelizableModel):
        name: str | None = None
        special_title: str | None = None

    target: int
    member_id: int
    info: Info


class SetGroupMemberInfoResponse(MiraiAPIResponse, StandardResponse):
    pass


# Event

class RespondNewFriendRequestEventRequest(MiraiAPIRequest, RespondEventRequest):
    pass


class RespondNewFriendRequestEventResponse(MiraiAPIResponse, StandardResponse):
    pass


class RespondMemberJoinRequestEventRequest(MiraiAPIRequest, RespondEventRequest):
    pass


class RespondMemberJoinRequestEventResponse(MiraiAPIResponse, StandardResponse):
    pass


class RespondBotInvitedJoinGroupRequestEventRequest(MiraiAPIRequest, RespondEventRequest):
    pass


class RespondBotInvitedJoinGroupRequestEventResponse(MiraiAPIResponse, StandardResponse):
    pass
