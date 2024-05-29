import sys
import os

import pytest
from loguru import logger

logger.remove()
logger.add(sys.stdout, level="DEBUG")


@pytest.fixture(autouse=True)
def _mock_dicerobot(monkeypatch):
    def _init_logger():
        logger.debug("Mocking init_logger")

    def _init_database():
        logger.debug("Mocking init_database")

    def _clean_database():
        logger.debug("Mocking clean_database")

    def _init_config():
        logger.debug("Mocking init_config")

    def _save_config():
        logger.debug("Mocking save_config")

    monkeypatch.setattr("app.logger", logger)
    monkeypatch.setattr("app.init_logger", _init_logger)
    monkeypatch.setattr("app.init_database", _init_database)
    monkeypatch.setattr("app.clean_database", _clean_database)
    monkeypatch.setattr("app.init_config", _init_config)
    monkeypatch.setattr("app.save_config", _save_config)


@pytest.fixture(autouse=True)
def _mock_mirai(monkeypatch):
    from app.models.network.mirai import (
        GetPluginInfoResponse, GetBotListResponse, GetFriendListResponse, GetGroupListResponse,
        GetGroupMemberListResponse, GetBotProfileResponse, GetFriendProfileResponse, GetGroupMemberProfileResponse,
        GetUserProfileResponse, SendFriendMessageRequest, SendFriendMessageResponse, SendGroupMessageRequest,
        SendGroupMessageResponse, SendTempMessageRequest, SendTempMessageResponse, SendNudgeMessageRequest,
        SendNudgeMessageResponse, RecallMessageRequest, RecallMessageResponse, DeleteFriendRequest,
        DeleteFriendResponse, GetGroupMemberInfoResponse, SetGroupMemberInfoRequest, SetGroupMemberInfoResponse
    )

    def _get_plugin_info() -> GetPluginInfoResponse:
        logger.debug("Mocking get_plugin_info")

        return GetPluginInfoResponse(
            code=0,
            msg="Success",
            data=GetPluginInfoResponse.Data(
                version="2.10.0"
            )
        )

    def _get_bot_list() -> GetBotListResponse:
        logger.debug("Mocking get_bot_list")

        return GetBotListResponse(
            code=0,
            msg="Success",
            data=[10000]
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

    def _get_group_member_list(_target: int) -> GetGroupMemberListResponse:
        logger.debug(f"Mocking get_group_member_list, target: {_target}")

        return GetGroupMemberListResponse(
            code=0,
            msg="Success",
            data=[
                GetGroupMemberListResponse.GroupMember(
                    id=88888,
                    member_name="Kaworu",
                    permission="ADMINISTRATOR",
                    special_title="",
                    join_timestamp=1600000000,
                    last_speak_timestamp=1650000000,
                    mute_time_remaining=0,
                    group=GetGroupMemberListResponse.GroupMember.Group(
                        id=12345,
                        name="Nerv",
                        permission="MEMBER"
                    )
                )
            ]
        )

    def _get_bot_profile() -> GetBotProfileResponse:
        logger.debug("Mocking get_bot_profile")

        return GetBotProfileResponse(
            nickname="Lilith",
            email="lilith@nerv.jp",
            age=1,
            level=1,
            sign="I'm Lilith",
            sex="UNKNOWN"
        )

    def _get_friend_profile(_target: int) -> GetFriendProfileResponse:
        logger.debug(f"Mocking get_friend_profile, target: {_target}")

        return GetFriendProfileResponse(
            nickname="Kaworu",
            email="kaworu@nerv.jp",
            age=1,
            level=1,
            sign="僕は君に会う為に生まれてきたんだね",
            sex="Male"
        )

    def _get_group_member_profile(_target: int, _member_id: int) -> GetGroupMemberProfileResponse:
        logger.debug(f"Mocking get_group_member_profile, target: {_target}, member ID: {_member_id}")

        return GetGroupMemberProfileResponse(
            nickname="Kaworu",
            email="kaworu@nerv.jp",
            age=1,
            level=1,
            sign="僕は君に会う為に生まれてきたんだね",
            sex="Male"
        )

    def _get_user_profile(_target: int) -> GetUserProfileResponse:
        logger.debug(f"Mocking get_user_profile, target: {_target}")

        return GetUserProfileResponse(
            nickname="Kaworu",
            email="kaworu@nerv.jp",
            age=1,
            level=1,
            sign="僕は君に会う為に生まれてきたんだね",
            sex="Male"
        )

    def _send_friend_message(_request: SendFriendMessageRequest) -> SendFriendMessageResponse:
        logger.debug(f"Mocking send_friend_message, request: {_request.model_dump()}")

        return SendFriendMessageResponse(
            code=0,
            msg="Success",
            message_id=1
        )

    def _send_group_message(_request: SendGroupMessageRequest) -> SendGroupMessageResponse:
        logger.debug(f"Mocking send_group_message, request: {_request.model_dump()}")

        return SendGroupMessageResponse(
            code=0,
            msg="Success",
            message_id=1
        )

    def _send_temp_message(_request: SendTempMessageRequest) -> SendTempMessageResponse:
        logger.debug(f"Mocking send_temp_message, request: {_request.model_dump()}")

        return SendTempMessageResponse(
            code=0,
            msg="Success",
            message_id=1
        )

    def _send_nudge_message(_request: SendNudgeMessageRequest) -> SendNudgeMessageResponse:
        logger.debug(f"Mocking send_nudge_message, request: {_request.model_dump()}")

        return SendNudgeMessageResponse(
            code=0,
            msg="Success",
            message_id=1
        )

    def _recall_message(_request: RecallMessageRequest) -> RecallMessageResponse:
        logger.debug(f"Mocking recall_message, request: {_request.model_dump()}")

        return RecallMessageResponse(
            code=0,
            msg="Success"
        )

    def _delete_friend(_request: DeleteFriendRequest) -> DeleteFriendResponse:
        logger.debug(f"Mocking delete_friend, request: {_request.model_dump()}")

        return DeleteFriendResponse(
            code=0,
            msg="Success"
        )

    def _get_group_member_info(_target: int, _member_id: int) -> GetGroupMemberInfoResponse:
        logger.debug(f"Mocking get_group_member_info, target: {_target}, member ID: {_member_id}")

        return GetGroupMemberInfoResponse(
            id=88888,
            member_name="Kaworu",
            permission="ADMINISTRATOR",
            special_title="",
            join_timestamp=1600000000,
            last_speak_timestamp=1650000000,
            mute_time_remaining=0,
            active=GetGroupMemberInfoResponse.Active(
                rank=1,
                point=100,
                honors=["群聊之火"],
                temperature=100
            ),
            group=GetGroupMemberInfoResponse.Group(
                id=12345,
                name="Nerv",
                permission="MEMBER"
            )
        )

    def _set_group_member_info(_request: SetGroupMemberInfoRequest) -> SetGroupMemberInfoResponse:
        logger.debug(f"Mocking set_group_member_info, request: {_request.model_dump()}")

        return SetGroupMemberInfoResponse(
            code=0,
            msg="Success"
        )

    _MIRAI_MOCKS = {
        "app.network.mirai.get_plugin_info": _get_plugin_info,
        "app.network.mirai.get_bot_list": _get_bot_list,
        "app.network.mirai.get_friend_list": _get_friend_list,
        "app.network.mirai.get_group_list": _get_group_list,
        "app.network.mirai.get_group_member_list": _get_group_member_list,
        "app.network.mirai.get_bot_profile": _get_bot_profile,
        "app.network.mirai.get_friend_profile": _get_friend_profile,
        "app.network.mirai.get_group_member_profile": _get_group_member_profile,
        "app.network.mirai.get_user_profile": _get_user_profile,
        "app.network.mirai.send_friend_message": _send_friend_message,
        "app.network.mirai.send_group_message": _send_group_message,
        "app.network.mirai.send_temp_message": _send_temp_message,
        "app.network.mirai.send_nudge_message": _send_nudge_message,
        "app.network.mirai.recall_message": _recall_message,
        "app.network.mirai.delete_friend": _delete_friend,
        "app.network.mirai.get_group_member_info": _get_group_member_info,
        "app.network.mirai.set_group_member_info": _set_group_member_info,
        "plugin.mirai_send_friend_message": _send_friend_message,
        "plugin.mirai_send_group_message": _send_group_message,
        "plugin.mirai_send_temp_message": _send_temp_message,
    }

    for target, replacement in _MIRAI_MOCKS.items():
        monkeypatch.setattr(target, replacement)


@pytest.fixture()
def client():
    from app import dicerobot
    from fastapi.testclient import TestClient

    with TestClient(dicerobot) as client:
        yield client


@pytest.fixture()
def openai():
    from app.config import plugin_settings

    plugin_settings._plugin_settings["dicerobot.chat"]["api_key"] = os.environ.get("TEST_OPENAI_API_KEY") or ""
    plugin_settings._plugin_settings["dicerobot.conversation"]["api_key"] = os.environ.get("TEST_OPENAI_API_KEY") or ""
