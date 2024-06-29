from typing import Any

from ...enum import Sex, Role
from .. import BaseModel

__all__ = [
    "GetLoginInfoResponse",
    "GetFriendListResponse",
    "GetGroupInfoResponse",
    "GetGroupListResponse",
    "GetGroupMemberInfoResponse",
    "GetGroupMemberListResponse",
    "SendPrivateMessageResponse",
    "SendGroupMessageResponse",
    "SetGroupCardResponse",
    "SetGroupLeaveResponse",
    "SetFriendAddRequestResponse",
    "SetGroupAddRequestResponse"
]


class NapCatResponse(BaseModel):
    status: str
    retcode: int
    data: Any
    message: str
    wording: str


class LoginInfo(BaseModel):
    user_id: int
    nickname: str


class UserInfo(BaseModel):
    user_id: int
    nickname: str
    remark: str
    sex: Sex
    level: int


class GroupInfo(BaseModel):
    group_id: int
    group_name: str
    member_count: int
    max_member_count: int


class SendMessageData(BaseModel):
    message_id: int


class GroupMemberInfo(BaseModel):
    group_id: int
    user_id: int
    nickname: str
    card: str
    sex: Sex
    age: int
    area: str
    level: str
    qq_level: int
    join_time: int
    last_sent_time: int
    title_expire_time: int
    unfriendly: bool
    card_changeable: bool
    is_robot: bool
    shut_up_timestamp: int
    role: Role
    title: str


class GetLoginInfoResponse(NapCatResponse):
    data: LoginInfo


class GetFriendListResponse(NapCatResponse):
    data: list[UserInfo]


class GetGroupInfoResponse(NapCatResponse):
    data: GroupInfo


class GetGroupListResponse(NapCatResponse):
    data: list[GroupInfo]


class GetGroupMemberInfoResponse(NapCatResponse):
    data: GroupMemberInfo


class GetGroupMemberListResponse(NapCatResponse):
    data: list[GroupMemberInfo]


class SendPrivateMessageResponse(NapCatResponse):
    data: SendMessageData


class SendGroupMessageResponse(NapCatResponse):
    data: SendMessageData


class SetGroupCardResponse(NapCatResponse):
    data: None = None


class SetGroupLeaveResponse(NapCatResponse):
    data: None = None


class SetFriendAddRequestResponse(NapCatResponse):
    data: None = None


class SetGroupAddRequestResponse(NapCatResponse):
    data: None = None
