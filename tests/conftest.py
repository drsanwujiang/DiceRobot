from unittest.mock import patch

import pytest

from app.log import logger
from app.internal.network.mirai import (
    GetBotListResponse, GetBotProfileResponse, GetFriendListResponse, GetGroupListResponse, GetFriendProfileResponse,
    GetGroupMemberProfileResponse, SetGroupMemberInfoResponse, SendFriendMessageResponse, SendGroupMessageResponse,
    SendTempMessageResponse, RespondNewFriendRequestEventResponse, RespondBotInvitedJoinGroupRequestEventResponse
)


def _get_bot_list() -> GetBotListResponse:
    logger.debug("Mocking get_bot_list")

    return GetBotListResponse(
        code=0,
        msg="Success",
        data=[10000]
    )


def _get_bot_profile() -> GetBotProfileResponse:
    logger.debug("Mocking get_bot_profile")

    return GetBotProfileResponse(
        nickname="Lilith",
        email="lilith@nerv.jp",
        age=1,
        level=1,
        sign="I'm Lilith",
        sex="Unknown"
    )


def _get_friend_list() -> GetFriendListResponse:
    logger.debug("Mocking get_friend_list")

    return GetFriendListResponse(
        code=0,
        msg="Success",
        data=[
            GetFriendListResponse.Friend(
                id=88888,
                nickname="Kaworu",
                remark="Kaworu"
            )
        ]
    )


def _get_group_list() -> GetGroupListResponse:
    logger.debug("Mocking get_group_list")

    return GetGroupListResponse(
        code=0,
        msg="Success",
        data=[
            GetGroupListResponse.Group(
                id=12345,
                name="Nerv",
                permission="MEMBER"
            )
        ]
    )


def _get_friend_profile(friend_id: int) -> GetFriendProfileResponse:
    logger.debug(f"Mocking get_friend_profile, friend ID: {friend_id}")

    return GetFriendProfileResponse(
        nickname="Kaworu",
        email="kaworu@nerv.jp",
        age=1,
        level=1,
        sign="僕は君に会う為に生まれてきたんだね",
        sex="Male"
    )


def _get_group_member_profile(group_id: int, member_id: int) -> GetGroupMemberProfileResponse:
    logger.debug(f"Mocking get_group_member_profile, group ID: {group_id}, member ID: {member_id}")

    return GetGroupMemberProfileResponse(
        nickname="Kaworu",
        email="kaworu@nerv.jp",
        age=1,
        level=1,
        sign="僕は君に会う為に生まれてきたんだね",
        sex="Male"
    )


def _set_group_member_info(data: dict) -> SetGroupMemberInfoResponse:
    logger.debug(f"Mocking set_group_member_info, data: {data}")

    return SetGroupMemberInfoResponse(
        code=0,
        msg="Success"
    )


def _send_friend_message(data: dict) -> SendFriendMessageResponse:
    logger.debug(f"Mocking send_friend_message, data: {data}")

    return SendFriendMessageResponse(
        code=0,
        msg="Success",
        messageId=1
    )


def _send_group_message(data: dict) -> SendGroupMessageResponse:
    logger.debug(f"Mocking send_group_message, data: {data}")

    return SendGroupMessageResponse(
        code=0,
        msg="Success",
        messageId=1
    )


def _send_temp_message(data: dict) -> SendTempMessageResponse:
    logger.debug(f"Mocking send_temp_message, data: {data}")

    return SendTempMessageResponse(
        code=0,
        msg="Success",
        messageId=1
    )


def _respond_new_friend_request_event(data: dict) -> RespondNewFriendRequestEventResponse:
    logger.debug(f"Mocking respond_new_friend_request_event, data: {data}")

    return RespondNewFriendRequestEventResponse(
        code=0,
        msg="Success"
    )


def _respond_bot_invited_join_group_request_event(data: dict) -> RespondBotInvitedJoinGroupRequestEventResponse:
    logger.debug(f"Mocking respond_bot_invited_join_group_request_event, data: {data}")

    return RespondBotInvitedJoinGroupRequestEventResponse(
        code=0,
        msg="Success"
    )


_MOCKS = {
    "app.internal.network.mirai.get_bot_list": _get_bot_list,
    "app.internal.network.mirai.get_bot_profile": _get_bot_profile,
    "app.internal.network.mirai.get_friend_list": _get_friend_list,
    "app.internal.network.mirai.get_group_list": _get_group_list,
    "app.internal.network.mirai.get_friend_profile": _get_friend_profile,
    "app.internal.network.mirai.get_group_member_profile": _get_group_member_profile,
    "app.internal.network.mirai.set_group_member_info": _set_group_member_info,
    "app.internal.network.mirai.send_friend_message": _send_friend_message,
    "app.internal.network.mirai.send_group_message": _send_group_message,
    "app.internal.network.mirai.send_temp_message": _send_temp_message,
    "app.internal.network.mirai.respond_new_friend_request_event": _respond_new_friend_request_event,
    "app.internal.network.mirai.respond_bot_invited_join_group_request_event": _respond_bot_invited_join_group_request_event
}


@pytest.fixture(autouse=True)
def _mock_mirai_api():
    mocks = {}

    for target, replacement in _MOCKS.items():
        mocks[target] = patch(target, replacement)
        mocks[target].start()

    yield mocks

    for patcher in mocks.values():
        patcher.stop()


@pytest.fixture()
def client():
    from app import dicerobot
    from fastapi.testclient import TestClient

    with TestClient(dicerobot) as client:
        yield client
