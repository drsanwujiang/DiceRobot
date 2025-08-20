from unittest.mock import MagicMock, AsyncMock, call
import os

import pytest

from app.context import AppContext
from app.actuators.napcat import NapCatActuator

pytestmark = pytest.mark.asyncio


async def test_check_running(context: AppContext, monkeypatch: pytest.MonkeyPatch) -> None:
    command_mock = AsyncMock()
    monkeypatch.setattr("app.actuators.napcat.run_command_wait", command_mock)

    command_mock.return_value = 0
    assert await context.napcat_actuator.check_running() is True
    command_mock.assert_called_once_with("systemctl is-active --quiet napcat")

    command_mock.return_value = 1
    assert await context.napcat_actuator.check_running() is False


async def test_remove(context: AppContext, monkeypatch: pytest.MonkeyPatch) -> None:
    os_remove_mock = MagicMock()
    rmtree_mock = MagicMock()
    command_mock = AsyncMock()
    monkeypatch.setattr("os.path.isfile", MagicMock(return_value=True))
    monkeypatch.setattr("os.remove", os_remove_mock)
    monkeypatch.setattr("shutil.rmtree", rmtree_mock)
    monkeypatch.setattr("app.actuators.napcat.run_command_wait", command_mock)

    await context.napcat_actuator.remove()
    os_remove_mock.assert_called_once_with(context.napcat_actuator.service_path)
    rmtree_mock.assert_called_once_with(context.napcat_actuator.context.settings.napcat.dir.base, ignore_errors=True)
    command_mock.assert_called_once_with("systemctl daemon-reload")


async def test_start(context: AppContext, monkeypatch: pytest.MonkeyPatch) -> None:
    rmtree_mock = MagicMock()
    aiofiles_open_mock = MagicMock()
    command_mock = AsyncMock()
    monkeypatch.setattr("os.path.isdir", MagicMock(return_value=True))
    monkeypatch.setattr("shutil.rmtree", rmtree_mock)
    monkeypatch.setattr("aiofiles.open", aiofiles_open_mock)
    monkeypatch.setattr("app.actuators.napcat.run_command_wait", command_mock)
    context.settings.napcat.account = 123456789

    await context.napcat_actuator.start()
    rmtree_mock.assert_called_once_with(context.settings.napcat.dir.logs)
    assert aiofiles_open_mock.call_count == 4
    assert call(
        os.path.join(context.settings.napcat.dir.base, context.napcat_actuator.env_path), "w", encoding="utf-8"
    ) in aiofiles_open_mock.mock_calls
    assert call(
        os.path.join(context.settings.napcat.dir.config, "napcat.json"), "w", encoding="utf-8"
    ) in aiofiles_open_mock.mock_calls
    assert call(
        os.path.join(context.settings.napcat.dir.config, f"napcat_{context.settings.napcat.account}.json"), "w", encoding="utf-8"
    ) in aiofiles_open_mock.mock_calls
    assert call(
        os.path.join(context.settings.napcat.dir.config, f"onebot11_{context.settings.napcat.account}.json"), "w", encoding="utf-8"
    ) in aiofiles_open_mock.mock_calls
    command_mock.assert_called_once_with("systemctl start napcat")


async def test_stop(monkeypatch: pytest.MonkeyPatch) -> None:
    command_mock = AsyncMock()
    monkeypatch.setattr("app.actuators.napcat.run_command_wait", command_mock)

    await NapCatActuator.stop()
    command_mock.assert_called_once_with("systemctl stop napcat")
