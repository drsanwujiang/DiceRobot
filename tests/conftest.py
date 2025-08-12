from typing import Generator
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

from app import dicerobot
from app.context import AppContext
from app.dependencies import get_app_context
from app.database import Base
from app.managers.database import DatabaseManager
from app.managers.config import ConfigManager
from app.managers.data import DataManager
from app.managers.dispatch import DispatchManager
from app.managers.task import TaskManager
from app.managers.network import NetworkManager
from app.actuators.app import AppActuator
from app.actuators.qq import QQActuator
from app.actuators.napcat import NapCatActuator


@pytest.fixture(autouse=True)
def prepare_environments(tmp_path: pathlib.Path) -> None:
    os.environ["DICEROBOT_DEBUG"] = "1"
    os.environ["DICEROBOT_DATABASE"] = str(tmp_path / "test.db")
    os.environ["DICEROBOT_LOG_DIR"] = str(tmp_path / "logs")
    os.environ["DICEROBOT_LOG_LEVEL"] = "DEBUG"


@pytest.fixture(autouse=True)
def prepare_logger() -> None:
    logger.remove()
    logger.add(sys.stdout, level="DEBUG", diagnose=True)


@pytest.fixture
def monkeypatch() -> Generator[pytest.MonkeyPatch]:
    with pytest.MonkeyPatch.context() as mp:
        yield mp


@pytest_asyncio.fixture(scope="session")
async def db_engine() -> AsyncGenerator[AsyncEngine]:
    engine = create_async_engine("sqlite+aiosqlite:///:memory:?_pragma=journal_mode=wal")

    async with engine.begin() as conn:
        await conn.run_sync(Base.metadata.create_all)

    yield engine
    await engine.dispose()


@pytest_asyncio.fixture
async def db_session(db_engine: AsyncEngine) -> AsyncGenerator[AsyncSession]:
    async with async_sessionmaker(db_engine, expire_on_commit=False).begin() as session:
        yield session


@pytest_asyncio.fixture
async def context(db_engine: AsyncEngine, tmp_path: pathlib.Path, monkeypatch: pytest.MonkeyPatch) -> AppContext:
    context = AppContext()
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
    context.network_manager = NetworkManager(context)
    context.task_manager = TaskManager(context)
    context.app_actuator = AppActuator(context)
    context.qq_actuator = QQActuator(context)
    context.napcat_actuator = NapCatActuator(context)
    return context


@pytest.fixture
def test_app(context: AppContext) -> Generator[FastAPI]:
    dicerobot.dependency_overrides[get_app_context] = lambda: context
    yield dicerobot
    dicerobot.dependency_overrides.clear()


@pytest_asyncio.fixture
async def client(test_app: FastAPI) -> AsyncGenerator[AsyncClient]:
    async with AsyncClient(transport=ASGITransport(app=test_app), base_url="http://test") as client:
        yield client


@pytest_asyncio.fixture
async def auth_token(client: AsyncClient, context: AppContext) -> str:
    test_password = "testpassword"
    context.settings.update_security({
        "admin": {
            "password": test_password
        }
    })

    response = await client.post("/auth", json={"password": test_password})
    assert response.status_code == 200
    return response.json()["data"]["token"]


@pytest.fixture
def auth_headers(auth_token: str) -> dict[str, str]:
    return {
        "Authorization": f"Bearer {auth_token}"
    }


@pytest.fixture
def authed_client(client: AsyncClient, auth_headers: dict[str, str]) -> AsyncClient:
    client.headers.update(auth_headers)
    return client


@pytest.fixture
def config_manager(context: AppContext) -> ConfigManager:
    return context.config_manager


@pytest.fixture
def dispatch_manager(context: AppContext) -> DispatchManager:
    return context.dispatch_manager


@pytest.fixture
def app_actuator(context: AppContext) -> AppActuator:
    return context.app_actuator


@pytest.fixture
def qq_actuator(context: AppContext) -> QQActuator:
    return context.qq_actuator


@pytest.fixture
def napcat_actuator(context: AppContext) -> NapCatActuator:
    return context.napcat_actuator
