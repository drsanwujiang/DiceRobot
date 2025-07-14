from ..models.network.napcat import (
    GetLoginInfoResponse, GetFriendListResponse, GetGroupInfoResponse, GetGroupListResponse, GetGroupMemberInfoResponse,
    GetGroupMemberListResponse, GetImageResponse, SendPrivateMessageResponse, SendGroupMessageResponse,
    SetGroupCardResponse, SetGroupLeaveResponse, SetFriendAddRequestResponse, SetGroupAddRequestResponse
)
from ..config import settings
from ..enum import GroupAddRequestSubType
from ..models.report.segment import Segment
from . import client

__all__ = [
    "get_login_info",
    "get_friend_list",
    "get_group_info",
    "get_group_list",
    "get_group_member_info",
    "get_group_member_list",
    "get_image",
    "send_private_message",
    "send_group_message",
    "set_group_card",
    "set_group_leave",
    "set_friend_add_request",
    "set_group_add_request"
]


async def get_login_info() -> GetLoginInfoResponse:
    return GetLoginInfoResponse.model_validate((await client.get(
        settings.napcat.api.base_url + "/get_login_info"
    )).json())


async def get_friend_list() -> GetFriendListResponse:
    return GetFriendListResponse.model_validate((await client.get(
        settings.napcat.api.base_url + "/get_friend_list"
    )).json())


async def get_group_info(group_id: int, no_cache: bool = False) -> GetGroupInfoResponse:
    return GetGroupInfoResponse.model_validate((await client.get(
        settings.napcat.api.base_url + "/get_group_info",
        params={
            "group_id": group_id,
            "no_cache": no_cache
        }
    )).json())


async def get_group_list() -> GetGroupListResponse:
    return GetGroupListResponse.model_validate((await client.get(
        settings.napcat.api.base_url + "/get_group_list"
    )).json())


async def get_group_member_info(group_id: int, user_id: int, no_cache: bool = False) -> GetGroupMemberInfoResponse:
    return GetGroupMemberInfoResponse.model_validate((await client.get(
        settings.napcat.api.base_url + "/get_group_member_info",
        params={
            "group_id": group_id,
            "user_id": user_id,
            "no_cache": no_cache
        }
    )).json())


async def get_group_member_list(group_id: int) -> GetGroupMemberListResponse:
    return GetGroupMemberListResponse.model_validate((await client.get(
        settings.napcat.api.base_url + "/get_group_member_list",
        params={
            "group_id": group_id
        }
    )).json())


async def get_image(file: str) -> GetImageResponse:
    return GetImageResponse.model_validate((await client.get(
        settings.napcat.api.base_url + "/get_image",
        params={
            "file": file
        }
    )).json())


async def send_private_message(
    user_id: int,
    message: list[Segment],
    auto_escape: bool = False
) -> SendPrivateMessageResponse:
    return SendPrivateMessageResponse.model_validate((await client.post(
        settings.napcat.api.base_url + "/send_private_msg",
        json={
            "user_id": user_id,
            "message": [segment.model_dump() for segment in message],
            "auto_escape": auto_escape
        }
    )).json())


async def send_group_message(
    group_id: int,
    message: list[Segment],
    auto_escape: bool = False
) -> SendGroupMessageResponse:
    return SendGroupMessageResponse.model_validate((await client.post(
        settings.napcat.api.base_url + "/send_group_msg",
        json={
            "group_id": group_id,
            "message": [segment.model_dump() for segment in message],
            "auto_escape": auto_escape
        }
    )).json())


async def set_group_card(
    group_id: int,
    user_id: int,
    card: str = ""
) -> SetGroupCardResponse:
    return SetGroupCardResponse.model_validate((await client.post(
        settings.napcat.api.base_url + "/set_group_card",
        json={
            "group_id": group_id,
            "user_id": user_id,
            "card": card
        }
    )).json())


async def set_group_leave(
    group_id: int,
    is_dismiss: bool = False
) -> SetGroupLeaveResponse:
    return SetGroupLeaveResponse.model_validate((await client.post(
        settings.napcat.api.base_url + "/set_group_leave",
        json={
            "group_id": group_id,
            "is_dismiss": is_dismiss
        }
    )).json())


async def set_friend_add_request(
    flag: str,
    approve: bool,
    remark: str = ""
) -> SetFriendAddRequestResponse:
    return SetFriendAddRequestResponse.model_validate((await client.post(
        settings.napcat.api.base_url + "/set_friend_add_request",
        json={
            "flag": flag,
            "approve": approve,
            "remark": remark
        }
    )).json())


async def set_group_add_request(
    flag: str,
    sub_type: GroupAddRequestSubType,
    approve: bool,
    reason: str = ""
) -> SetGroupAddRequestResponse:
    return SetGroupAddRequestResponse.model_validate((await client.post(
        settings.napcat.api.base_url + "/set_group_add_request",
        json={
            "flag": flag,
            "sub_type": sub_type.value,
            "approve": approve,
            "reason": reason
        }
    )).json())
