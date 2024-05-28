from ..models.network.mirai import (
    GetPluginInfoResponse, GetBotListResponse, GetFriendListResponse, GetGroupListResponse, GetGroupMemberListResponse,
    GetBotProfileResponse, GetFriendProfileResponse, GetGroupMemberProfileResponse, GetUserProfileResponse,
    SendFriendMessageRequest, SendFriendMessageResponse, SendGroupMessageRequest, SendGroupMessageResponse,
    SendTempMessageRequest, SendTempMessageResponse, SendNudgeMessageRequest, SendNudgeMessageResponse,
    RecallMessageRequest, RecallMessageResponse, GetRoamingMessagesRequest, GetRoamingMessagesResponse,
    DeleteFriendRequest, DeleteFriendResponse, MuteGroupMemberRequest, MuteGroupMemberResponse,
    UnmuteGroupMemberRequest, UnmuteGroupMemberResponse, KickGroupMemberRequest, KickGroupMemberResponse,
    QuitGroupRequest, QuitGroupResponse, MuteAllRequest, MuteAllResponse, UnmuteAllRequest, UnmuteAllResponse,
    GetGroupMemberInfoResponse, SetGroupMemberInfoRequest, SetGroupMemberInfoResponse,
    RespondNewFriendRequestEventRequest, RespondNewFriendRequestEventResponse, RespondMemberJoinRequestEventRequest,
    RespondMemberJoinRequestEventResponse, RespondBotInvitedJoinGroupRequestEventRequest,
    RespondBotInvitedJoinGroupRequestEventResponse
)
from ..config import settings
from . import client


# Plugin info

def get_plugin_info() -> GetPluginInfoResponse:
    return GetPluginInfoResponse.model_validate(client.get(
        settings["mirai"]["api"]["base_url"] + "/about"
    ).json())


def get_bot_list() -> GetBotListResponse:
    return GetBotListResponse.model_validate(client.get(
        settings["mirai"]["api"]["base_url"] + "/botList"
    ).json())


# Account info

def get_friend_list() -> GetFriendListResponse:
    return GetFriendListResponse.model_validate(client.get(
        settings["mirai"]["api"]["base_url"] + "/friendList"
    ).json())


def get_group_list() -> GetGroupListResponse:
    return GetGroupListResponse.model_validate(client.get(
        settings["mirai"]["api"]["base_url"] + "/groupList"
    ).json())


def get_group_member_list(target: int) -> GetGroupMemberListResponse:
    return GetGroupMemberListResponse.model_validate(client.get(
        settings["mirai"]["api"]["base_url"] + "/memberList",
        params={"target": target}
    ).json())


def get_bot_profile() -> GetBotProfileResponse:
    return GetBotProfileResponse.model_validate(client.get(
        settings["mirai"]["api"]["base_url"] + "/botProfile"
    ).json())


def get_friend_profile(target: int) -> GetFriendProfileResponse:
    return GetFriendProfileResponse.model_validate(client.get(
        settings["mirai"]["api"]["base_url"] + "/friendProfile",
        params={"target": target}
    ).json())


def get_group_member_profile(target: int, member_id: int) -> GetGroupMemberProfileResponse:
    return GetGroupMemberProfileResponse.model_validate(client.get(
        settings["mirai"]["api"]["base_url"] + "/memberProfile",
        params={"target": target, "memberId": member_id}
    ).json())


def get_user_profile(target: int) -> GetUserProfileResponse:
    return GetUserProfileResponse.model_validate(client.get(
        settings["mirai"]["api"]["base_url"] + "/userProfile",
        params={"target": target}
    ).json())


# Message

def send_friend_message(request: SendFriendMessageRequest) -> SendFriendMessageResponse:
    return SendFriendMessageResponse.model_validate(client.post(
        settings["mirai"]["api"]["base_url"] + "/sendFriendMessage",
        json=request.model_dump(by_alias=True)
    ).json())


def send_group_message(request: SendGroupMessageRequest) -> SendGroupMessageResponse:
    return SendGroupMessageResponse.model_validate(client.post(
        settings["mirai"]["api"]["base_url"] + "/sendGroupMessage",
        json=request.model_dump(by_alias=True)
    ).json())


def send_temp_message(request: SendTempMessageRequest) -> SendTempMessageResponse:
    return SendTempMessageResponse.model_validate(client.post(
        settings["mirai"]["api"]["base_url"] + "/sendTempMessage",
        json=request.model_dump(by_alias=True)
    ).json())


def send_nudge_message(request: SendNudgeMessageRequest) -> SendNudgeMessageResponse:
    return SendNudgeMessageResponse.model_validate(client.post(
        settings["mirai"]["api"]["base_url"] + "/sendNudge",
        json=request.model_dump(by_alias=True)
    ).json())


def recall_message(request: RecallMessageRequest) -> RecallMessageResponse:
    return RecallMessageResponse.model_validate(client.post(
        settings["mirai"]["api"]["base_url"] + "/recall",
        json=request.model_dump(by_alias=True)
    ).json())


def get_roaming_messages(request: GetRoamingMessagesRequest) -> GetRoamingMessagesResponse:
    return GetRoamingMessagesResponse.model_validate(client.post(
        settings["mirai"]["api"]["base_url"] + "/roamingMessages",
        json=request.model_dump(by_alias=True)
    ).json())


# Account management

def delete_friend(request: DeleteFriendRequest) -> DeleteFriendResponse:
    return DeleteFriendResponse.model_validate(client.post(
        settings["mirai"]["api"]["base_url"] + "/deleteFriend",
        json=request.model_dump(by_alias=True)
    ).json())


# Group management

def mute_group_member(request: MuteGroupMemberRequest) -> MuteGroupMemberResponse:
    return MuteGroupMemberResponse.model_validate(client.post(
        settings["mirai"]["api"]["base_url"] + "/mute",
        json=request.model_dump(by_alias=True)
    ).json())


def unmute_group_member(request: UnmuteGroupMemberRequest) -> UnmuteGroupMemberResponse:
    return UnmuteGroupMemberResponse.model_validate(client.post(
        settings["mirai"]["api"]["base_url"] + "/unmute",
        json=request.model_dump(by_alias=True)
    ).json())


def kick_group_member(request: KickGroupMemberRequest) -> KickGroupMemberResponse:
    return KickGroupMemberResponse.model_validate(client.post(
        settings["mirai"]["api"]["base_url"] + "/kick",
        json=request.model_dump(by_alias=True)
    ).json())


def quit_group(request: QuitGroupRequest) -> QuitGroupResponse:
    return QuitGroupResponse.model_validate(client.post(
        settings["mirai"]["api"]["base_url"] + "/quit",
        json=request.model_dump(by_alias=True)
    ).json())


def mute_all(request: MuteAllRequest) -> MuteAllResponse:
    return MuteAllResponse.model_validate(client.post(
        settings["mirai"]["api"]["base_url"] + "/muteAll",
        json=request.model_dump(by_alias=True)
    ).json())


def unmute_all(request: UnmuteAllRequest) -> UnmuteAllResponse:
    return UnmuteAllResponse.model_validate(client.post(
        settings["mirai"]["api"]["base_url"] + "/unmuteAll",
        json=request.model_dump(by_alias=True)
    ).json())


def get_group_member_info(target: int, member_id: int) -> GetGroupMemberInfoResponse:
    return GetGroupMemberInfoResponse.model_validate(client.get(
        settings["mirai"]["api"]["base_url"] + "/memberInfo",
        params={"target": target, "memberId": member_id}
    ).json())


def set_group_member_info(request: SetGroupMemberInfoRequest) -> SetGroupMemberInfoResponse:
    return SetGroupMemberInfoResponse.model_validate(client.post(
        settings["mirai"]["api"]["base_url"] + "/memberInfo",
        json=request.model_dump(by_alias=True)
    ).json())


# Event

def respond_new_friend_request_event(request: RespondNewFriendRequestEventRequest) -> RespondNewFriendRequestEventResponse:
    return RespondNewFriendRequestEventResponse.model_validate(client.post(
        settings["mirai"]["api"]["base_url"] + "/resp/newFriendRequestEvent",
        json=request.model_dump(by_alias=True)
    ).json())


def respond_member_join_request_event(request: RespondMemberJoinRequestEventRequest) -> RespondMemberJoinRequestEventResponse:
    return RespondMemberJoinRequestEventResponse.model_validate(client.post(
        settings["mirai"]["api"]["base_url"] + "/resp/memberJoinRequestEvent",
        json=request.model_dump(by_alias=True)
    ).json())


def respond_bot_invited_join_group_request_event(request: RespondBotInvitedJoinGroupRequestEventRequest) -> RespondBotInvitedJoinGroupRequestEventResponse:
    return RespondBotInvitedJoinGroupRequestEventResponse.model_validate(client.post(
        settings["mirai"]["api"]["base_url"] + "/resp/botInvitedJoinGroupRequestEvent",
        json=request.model_dump(by_alias=True)
    ).json())
