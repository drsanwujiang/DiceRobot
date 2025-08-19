from unittest.mock import DEFAULT, AsyncMock
import asyncio
import json

import pytest
from semver import Version
from sse_starlette import JSONServerSentEvent

from app.context import AppContext
from app.managers.task import TaskManager
from app.actuators.app import AppActuator
from app.enum import UpdateStatus

pytestmark = pytest.mark.asyncio


@pytest.fixture(autouse=True)
def prepare_app_actuator(monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setattr(AppActuator, "_get_latest_version", AsyncMock(return_value=Version.parse("4.1.0")))
    monkeypatch.setattr(AppActuator, "get_version", AsyncMock(return_value="4.0.0"))


async def test_update_not_needed(context: AppContext, monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setattr(AppActuator, "get_version", AsyncMock(return_value="4.99.0"))

    await context.app_actuator.update()
    assert context.app_actuator.update_status == UpdateStatus.COMPLETED


async def test_update(context: AppContext, monkeypatch: pytest.MonkeyPatch) -> None:
    download_mock = AsyncMock(return_value=True)
    monkeypatch.setattr(AppActuator, "_download_file", download_mock)
    install_mock = AsyncMock(return_value=True)
    monkeypatch.setattr(AppActuator, "_install", install_mock)

    await context.app_actuator.update()
    download_mock.assert_called_once()
    install_mock.assert_called_once()
    assert context.app_actuator.update_status == UpdateStatus.COMPLETED


async def test_update_download_fails(context: AppContext, monkeypatch: pytest.MonkeyPatch) -> None:
    download_mock = AsyncMock(return_value=False)
    monkeypatch.setattr(AppActuator, "_download_file", download_mock)
    install_mock = AsyncMock(return_value=True)
    monkeypatch.setattr(AppActuator, "_install", install_mock)

    await context.app_actuator.update()
    download_mock.assert_called_once()
    install_mock.assert_not_called()
    assert context.app_actuator.update_status == UpdateStatus.FAILED


async def test_update_install_fails(context: AppContext, monkeypatch: pytest.MonkeyPatch) -> None:
    download_mock = AsyncMock(return_value=True)
    monkeypatch.setattr(AppActuator, "_download_file", download_mock)
    install_mock = AsyncMock(return_value=False)
    monkeypatch.setattr(AppActuator, "_install", install_mock)

    await context.app_actuator.update()
    download_mock.assert_called_once()
    install_mock.assert_called_once()
    assert context.app_actuator.update_status == UpdateStatus.FAILED


async def test_update_stream_status(context: AppContext, monkeypatch: pytest.MonkeyPatch) -> None:
    async def delay(*_, **__) -> type[DEFAULT]:
        await asyncio.sleep(0.05)
        return DEFAULT

    monkeypatch.setattr(AppActuator, "_check_version", AsyncMock(side_effect=delay, return_value=Version.parse("4.1.0")))
    monkeypatch.setattr(AppActuator, "_download_file", AsyncMock(side_effect=delay, return_value=True))
    monkeypatch.setattr(AppActuator, "_install", AsyncMock(side_effect=delay, return_value=True))

    status = []

    async for event in context.app_actuator.create_update_stream(0.01):
        assert isinstance(event, JSONServerSentEvent)
        data = json.loads(event.data)
        assert "status" in data
        status.append(data["status"])

        if data["status"] in (UpdateStatus.COMPLETED, UpdateStatus.FAILED):
            break

    assert UpdateStatus.CHECKING in status
    assert UpdateStatus.DOWNLOADING in status
    assert UpdateStatus.INSTALLING in status
    assert UpdateStatus.COMPLETED in status


async def test_restart(context: AppContext, monkeypatch: pytest.MonkeyPatch) -> None:
    mock = AsyncMock()
    monkeypatch.setattr(TaskManager, "run_task_later", mock)
    await context.app_actuator.restart()
    mock.assert_called_once_with("dicerobot.restart", 1)
