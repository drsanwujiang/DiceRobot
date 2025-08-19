from typing import Generator
from unittest.mock import AsyncMock
import pathlib
import sys
import os
from collections.abc import AsyncGenerator

import pytest
import pytest_asyncio
from loguru import logger
from httpx import AsyncClient, ASGITransport
from sqlalchemy.ext.asyncio import create_async_engine, async_sessionmaker, AsyncEngine, AsyncSession
from fastapi import FastAPI

from app.exception_handlers import init_exception_handlers
from app.routers import init_routers
from app.context import AppContext
from app.database import Base
from app.managers.database import DatabaseManager
from app.managers.config import ConfigManager
from app.managers.data import DataManager
from app.managers.dispatch import DispatchManager
from app.managers.task import TaskManager
from app.actuators.app import AppActuator
from app.actuators.qq import QQActuator
from app.actuators.napcat import NapCatActuator
from app.models.network.napcat import (
    GetLoginInfoResponse, GetFriendListResponse, GetGroupInfoResponse, GetGroupListResponse,
    GetGroupMemberInfoResponse, GetGroupMemberListResponse, SendPrivateMessageResponse, SendGroupMessageResponse,
    SetGroupCardResponse, SetGroupLeaveResponse, SetFriendAddRequestResponse, SetGroupAddRequestResponse
)
from app.models.report.segment import Segment


@pytest.fixture
def monkeypatch() -> Generator[pytest.MonkeyPatch]:
    with pytest.MonkeyPatch.context() as mp:
        yield mp


@pytest.fixture(autouse=True)
def prepare_environments(tmp_path: pathlib.Path, monkeypatch: pytest.MonkeyPatch) -> None:
    debug = os.environ["DICEROBOT_DEBUG"] = "1"
    database = os.environ["DICEROBOT_DATABASE"] = str(tmp_path / "test.db")
    log_dir = os.environ["DICEROBOT_LOG_DIR"] = str(tmp_path / "logs")
    log_level = os.environ["DICEROBOT_LOG_LEVEL"] = "DEBUG"
    monkeypatch.setattr("app.globals.DEBUG", debug)
    monkeypatch.setattr("app.globals.DATABASE", database)
    monkeypatch.setattr("app.globals.LOG_DIR", log_dir)
    monkeypatch.setattr("app.globals.LOG_LEVEL", log_level)
    monkeypatch.setattr("app.exception_handlers.DEBUG", debug)
    monkeypatch.setattr("app.context.DEBUG", debug)
    monkeypatch.setattr("app.managers.dispatch.DEBUG", debug)


@pytest.fixture(autouse=True)
def prepare_logger() -> None:
    logger.remove()
    logger.add(sys.stdout, level="DEBUG", diagnose=True)


@pytest_asyncio.fixture(name="db_engine", scope="session")
async def prepare_database_engine() -> AsyncGenerator[AsyncEngine]:
    engine = create_async_engine("sqlite+aiosqlite:///:memory:?_pragma=journal_mode=wal")

    async with engine.begin() as conn:
        await conn.run_sync(Base.metadata.create_all)

    yield engine
    await engine.dispose()


@pytest_asyncio.fixture(name="db_session")
async def prepare_database_session(db_engine: AsyncEngine) -> AsyncGenerator[AsyncSession]:
    async with async_sessionmaker(db_engine, expire_on_commit=False).begin() as session:
        yield session


@pytest_asyncio.fixture(name="context")
async def prepare_context(db_engine: AsyncEngine, tmp_path: pathlib.Path, monkeypatch: pytest.MonkeyPatch) -> AppContext:
    context = AppContext()
    context.status.bot.id = 99999
    context.settings.update_application({
        "dir": {
            "base": str(tmp_path / "DiceRobot"),
            "data": str(tmp_path / "DiceRobot" / "data"),
            "temp": str(tmp_path / "DiceRobot" / "temp")
        }
    })
    context.settings.update_qq({
        "dir": {
            "base": str(tmp_path / "QQ" / "application"),
            "config": str(tmp_path / "QQ" / "config")
        }
    })
    context.settings.update_napcat({
        "dir": {
            "base": str(tmp_path / "NapCat"),
            "config": str(tmp_path / "NapCat" / "config"),
            "logs": str(tmp_path / "NapCat" / "logs")
        }
    })
    context.database_manager = DatabaseManager(context)
    context.database_manager.session = async_sessionmaker(db_engine, expire_on_commit=False)
    context.config_manager = ConfigManager(context)
    context.data_manager = DataManager(context)
    context.dispatch_manager = DispatchManager(context)
    context.task_manager = TaskManager(context)
    context.app_actuator = AppActuator(context)
    context.qq_actuator = QQActuator(context)
    context.napcat_actuator = NapCatActuator(context)
    return context


@pytest.fixture(autouse=True)
def mock_network_manager(context: AppContext, monkeypatch: pytest.MonkeyPatch) -> None:
    async def _get_login_info() -> GetLoginInfoResponse:
        logger.debug("Request NapCat API: get_login_info")

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

    async def _get_friend_list() -> GetFriendListResponse:
        logger.debug("Request NapCat API: get_friend_list")

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

    async def _get_group_info(*_) -> GetGroupInfoResponse:
        logger.debug("Request NapCat API: get_group_info")

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

    async def _get_group_list() -> GetGroupListResponse:
        logger.debug("Request NapCat API: get_group_list")

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

    async def _get_group_member_info(*_) -> GetGroupMemberInfoResponse:
        logger.debug("Request NapCat API: get_group_member_info")

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

    async def _get_group_member_list(*_) -> GetGroupMemberListResponse:
        logger.debug("Request NapCat API: get_group_member_list")

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

    async def _send_private_message(_: int, message: list[Segment], *__) -> SendPrivateMessageResponse:
        logger.debug(f"Request NapCat API: send_private_message. Message: {[segment.model_dump_json() for segment in message]}")

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

    async def _send_group_message(_: int, message: list[Segment], *__) -> SendGroupMessageResponse:
        logger.debug(f"Request NapCat API: send_group_message. Message: {[segment.model_dump_json() for segment in message]}")

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

    async def _set_group_card(*_) -> SetGroupCardResponse:
        logger.debug("Request NapCat API: set_group_card")

        return SetGroupCardResponse.model_validate({
            "status": "ok",
            "retcode": 0,
            "data": None,
            "message": "",
            "wording": "",
            "echo": None
        })

    async def _set_group_leave(*_) -> SetGroupLeaveResponse:
        logger.debug("Request NapCat API: set_group_leave")

        return SetGroupLeaveResponse.model_validate({
            "status": "ok",
            "retcode": 0,
            "data": None,
            "message": "",
            "wording": "",
            "echo": None
        })

    async def _set_friend_add_request(*_) -> SetFriendAddRequestResponse:
        logger.debug("Request NapCat API: set_friend_add_request")

        return SetFriendAddRequestResponse.model_validate({
            "status": "ok",
            "retcode": 0,
            "data": None,
            "message": "",
            "wording": "",
            "echo": None
        })

    async def _set_group_add_request(*_) -> SetGroupAddRequestResponse:
        logger.debug("Request NapCat API: set_group_add_request")

        return SetGroupAddRequestResponse.model_validate({
            "status": "ok",
            "retcode": 0,
            "data": None,
            "message": "",
            "wording": "",
            "echo": None
        })

    mock_manager = AsyncMock()
    mock_manager.napcat.get_login_info = AsyncMock(side_effect=_get_login_info)
    mock_manager.napcat.get_friend_list = AsyncMock(side_effect=_get_friend_list)
    mock_manager.napcat.get_group_info = AsyncMock(side_effect=_get_group_info)
    mock_manager.napcat.get_group_list = AsyncMock(side_effect=_get_group_list)
    mock_manager.napcat.get_group_member_info = AsyncMock(side_effect=_get_group_member_info)
    mock_manager.napcat.get_group_member_list = AsyncMock(side_effect=_get_group_member_list)
    mock_manager.napcat.send_private_message = AsyncMock(side_effect=_send_private_message)
    mock_manager.napcat.send_group_message = AsyncMock(side_effect=_send_group_message)
    mock_manager.napcat.set_group_card = AsyncMock(side_effect=_set_group_card)
    mock_manager.napcat.set_group_leave = AsyncMock(side_effect=_set_group_leave)
    mock_manager.napcat.set_friend_add_request = AsyncMock(side_effect=_set_friend_add_request)
    mock_manager.napcat.set_group_add_request = AsyncMock(side_effect=_set_group_add_request)
    context.network_manager = mock_manager


@pytest.fixture(name="test_app")
def prepare_app(context: AppContext) -> FastAPI:
    test_app = FastAPI()
    test_app.state.context = context
    init_exception_handlers(test_app)
    init_routers(test_app)
    return test_app


@pytest_asyncio.fixture
async def client(test_app: FastAPI) -> AsyncGenerator[AsyncClient]:
    async with AsyncClient(transport=ASGITransport(app=test_app), base_url="http://test") as client:
        yield client


@pytest_asyncio.fixture
async def authed_client(context: AppContext, client: AsyncClient) -> AsyncClient:
    test_password = "testpassword"
    context.settings.update_security({
        "admin": {
            "password": test_password
        }
    })

    response = await client.post("/auth", json={"password": test_password})
    assert response.status_code == 200
    result = response.json()

    client.headers.update({
        "Authorization": "Bearer " + result["data"]["token"]
    })
    return client
