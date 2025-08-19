from unittest.mock import AsyncMock
import pathlib
import os
import json

import pytest
from semver import Version

from app.context import AppContext
from app.actuators.qq import QQActuator

pytestmark = pytest.mark.asyncio


@pytest.fixture(autouse=True)
def prepare_qq_actuator(tmp_path: pathlib.Path, monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setattr(QQActuator, "_get_latest_version", AsyncMock(return_value=Version.parse("3.99.99-99999")))


async def test_installed_and_get_version(context: AppContext) -> None:
    assert context.qq_actuator.installed is False
    assert await context.qq_actuator.get_version() is None

    version = "3.0.0-00001"
    os.makedirs(os.path.dirname(os.path.join(context.settings.qq.dir.base, context.qq_actuator.package_json_path)), exist_ok=True)

    with open(os.path.join(context.settings.qq.dir.base, context.qq_actuator.package_json_path), "w", encoding="utf-8") as f:
        f.write(json.dumps({"version": version}))

    assert context.qq_actuator.installed is True
    assert await context.qq_actuator.get_version() == version


async def test_installed_and_get_version_invalid(context: AppContext):
    assert context.qq_actuator.installed is False
    assert await context.qq_actuator.get_version() is None

    os.makedirs(os.path.dirname(os.path.join(context.settings.qq.dir.base, context.qq_actuator.package_json_path)), exist_ok=True)

    with open(os.path.join(context.settings.qq.dir.base, context.qq_actuator.package_json_path), "w", encoding="utf-8") as f:
        f.write("{not-json")

    assert context.qq_actuator.installed is True
    assert await context.qq_actuator.get_version() is None


async def test_remove(context: AppContext, monkeypatch: pytest.MonkeyPatch):
    os.makedirs(context.settings.qq.dir.base, exist_ok=True)
    os.makedirs(context.settings.qq.dir.config, exist_ok=True)

    command_mock = AsyncMock()
    monkeypatch.setattr("app.actuators.qq.run_command_wait", command_mock)

    await context.qq_actuator.remove(purge=False)
    command_mock.assert_called_once()
    assert os.path.isdir(context.settings.qq.dir.base) is False
    assert os.path.isdir(context.settings.qq.dir.config) is True

    os.makedirs(context.settings.qq.dir.base, exist_ok=True)

    await context.qq_actuator.remove(purge=True)
    assert os.path.isdir(context.settings.qq.dir.base) is False
    assert os.path.isdir(context.settings.qq.dir.config) is False
