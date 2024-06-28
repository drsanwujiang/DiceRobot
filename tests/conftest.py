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
def _mock_napcat(monkeypatch):
    from app.models.network.napcat import (
        GetLoginInfoResponse, GetFriendListResponse, GetGroupInfoResponse, GetGroupListResponse,
        GetGroupMemberInfoResponse, GetGroupMemberListResponse, SendPrivateMessageResponse, SendGroupMessageResponse,
        SetGroupCardResponse, SetGroupLeaveResponse, SetFriendAddRequestResponse, SetGroupAddRequestResponse
    )
    from app.models.report.segment import Segment
    from app.enum import GroupAddRequestSubType

    def _get_login_info() -> GetLoginInfoResponse:
        logger.debug("Mocking get_login_info")

        return GetLoginInfoResponse.model_validate({
            "status": "ok",
            "retcode": 0,
            "data": {
                "user_id": 99999,
                "nickname": "Shinji"
            },
            "message": "",
            "wording": "",
            "echo": None
        })

    def _get_friend_list() -> GetFriendListResponse:
        logger.debug("Mocking get_friend_list")

        return GetFriendListResponse.model_validate({
            "status": "ok",
            "retcode": 0,
            "data": [
                {
                    "user_id": 88888,
                    "nickname": "Kaworu",
                    "remark": "",
                    "sex": "male",
                    "level": 0
                },
                {
                    "user_id": 99999,
                    "nickname": "Shinji",
                    "remark": "",
                    "sex": "male",
                    "level": 0
                }
            ],
            "message": "",
            "wording": "",
            "echo": None
        })

    def _get_group_info(group_id: int, no_cache: bool = False) -> GetGroupInfoResponse:
        logger.debug("Mocking get_group_info")

        return GetGroupInfoResponse.model_validate({
            "status": "ok",
            "retcode": 0,
            "data": {
                "group_id": 12345,
                "group_name": "Nerv",
                "member_count": 2,
                "max_member_count": 200
            },
            "message": "",
            "wording": "",
            "echo": None
        })

    def _get_group_list() -> GetGroupListResponse:
        logger.debug("Mocking get_group_list")

        return GetGroupListResponse.model_validate({
            "status": "ok",
            "retcode": 0,
            "data": [
                {
                    "group_id": 12345,
                    "group_name": "Nerv",
                    "member_count": 2,
                    "max_member_count": 200
                }
            ],
            "message": "",
            "wording": "",
            "echo": None
        })

    def _get_group_member_info(group_id: int, user_id: int, no_cache: bool = False) -> GetGroupMemberInfoResponse:
        logger.debug("Mocking get_group_member_info")

        return GetGroupMemberInfoResponse.model_validate({
            "status": "ok",
            "retcode": 0,
            "data": {
                "group_id": 12345,
                "user_id": 88888,
                "nickname": "Kaworu",
                "card": "",
                "sex": "male",
                "age": 0,
                "area": "",
                "level": "0",
                "qq_level": 0,
                "join_time": 0,
                "last_sent_time": 0,
                "title_expire_time": 0,
                "unfriendly": False,
                "card_changeable": True,
                "is_robot": False,
                "shut_up_timestamp": 0,
                "role": "owner",
                "title": ""
            },
            "message": "",
            "wording": "",
            "echo": None
        })

    def _get_group_member_list(group_id: int) -> GetGroupMemberListResponse:
        logger.debug("Mocking get_group_member_list")

        return GetGroupMemberListResponse.model_validate({
            "status": "ok",
            "retcode": 0,
            "data": [
                {
                    "group_id": 12345,
                    "user_id": 88888,
                    "nickname": "Kaworu",
                    "card": "",
                    "sex": "male",
                    "age": 0,
                    "area": "",
                    "level": "0",
                    "qq_level": 0,
                    "join_time": 0,
                    "last_sent_time": 0,
                    "title_expire_time": 0,
                    "unfriendly": False,
                    "card_changeable": True,
                    "is_robot": False,
                    "shut_up_timestamp": 0,
                    "role": "owner",
                    "title": ""
                },
                {
                    "group_id": 12345,
                    "user_id": 99999,
                    "nickname": "Shinji",
                    "card": "",
                    "sex": "male",
                    "age": 0,
                    "area": "",
                    "level": "0",
                    "qq_level": 0,
                    "join_time": 0,
                    "last_sent_time": 0,
                    "title_expire_time": 0,
                    "unfriendly": False,
                    "card_changeable": True,
                    "is_robot": False,
                    "shut_up_timestamp": 0,
                    "role": "admin",
                    "title": ""
                }
            ],
            "message": "",
            "wording": "",
            "echo": None
        })

    def _send_private_message(
        user_id: int,
        message: list[Segment],
        auto_escape: bool = False
    ) -> SendPrivateMessageResponse:
        logger.debug("Mocking send_private_message")

        return SendPrivateMessageResponse.model_validate({
            "status": "ok",
            "retcode": 0,
            "data": {
                "message_id": -1234567890
            },
            "message": "",
            "wording": "",
            "echo": None
        })

    def _send_group_message(
        group_id: int,
        message: list[Segment],
        auto_escape: bool = False
    ) -> SendGroupMessageResponse:
        logger.debug("Mocking send_group_message")

        return SendGroupMessageResponse.model_validate({
            "status": "ok",
            "retcode": 0,
            "data": {
                "message_id": -1234567890
            },
            "message": "",
            "wording": "",
            "echo": None
        })

    def _set_group_card(
        group_id: int,
        user_id: int,
        card: str = ""
    ) -> SetGroupCardResponse:
        logger.debug("Mocking set_group_card")

        return SetGroupCardResponse.model_validate({
            "status": "ok",
            "retcode": 0,
            "data": None,
            "message": "",
            "wording": "",
            "echo": None
        })

    def _set_group_leave(
        group_id: int,
        is_dismiss: bool = False
    ) -> SetGroupLeaveResponse:
        logger.debug("Mocking set_group_leave")

        return SetGroupLeaveResponse.model_validate({
            "status": "ok",
            "retcode": 0,
            "data": None,
            "message": "",
            "wording": "",
            "echo": None
        })

    def _set_friend_add_request(
        flag: str,
        approve: bool,
        remark: str = ""
    ) -> SetFriendAddRequestResponse:
        logger.debug("Mocking set_friend_add_request")

        return SetFriendAddRequestResponse.model_validate({
            "status": "ok",
            "retcode": 0,
            "data": None,
            "message": "",
            "wording": "",
            "echo": None
        })

    def _set_group_add_request(
        flag: str,
        sub_type: GroupAddRequestSubType,
        approve: bool,
        reason: str = ""
    ) -> SetGroupAddRequestResponse:
        logger.debug("Mocking set_group_add_request")

        return SetGroupAddRequestResponse.model_validate({
            "status": "ok",
            "retcode": 0,
            "data": None,
            "message": "",
            "wording": "",
            "echo": None
        })

    _NAPCAT_MOCKS = {
        "app.network.napcat.get_login_info": _get_login_info,
        "app.network.napcat.get_friend_list": _get_friend_list,
        "app.network.napcat.get_group_info": _get_group_info,
        "app.network.napcat.get_group_list": _get_group_list,
        "app.network.napcat.get_group_member_info": _get_group_member_info,
        "app.network.napcat.get_group_member_list": _get_group_member_list,
        "app.network.napcat.send_private_message": _send_private_message,
        "app.network.napcat.send_group_message": _send_group_message,
        "app.network.napcat.set_group_card": _set_group_card,
        "app.network.napcat.set_group_leave": _set_group_leave,
        "app.network.napcat.set_friend_add_request": _set_friend_add_request,
        "app.network.napcat.set_group_add_request": _set_group_add_request,
        "plugin.napcat_send_private_message": _send_private_message,
        "plugin.napcat_send_group_message": _send_group_message,
    }

    for target, replacement in _NAPCAT_MOCKS.items():
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
