from unittest.mock import AsyncMock, PropertyMock
import os
import json

import pytest
from httpx import AsyncClient

from app.context import AppContext
from app.actuators.qq import QQActuator
from app.actuators.napcat import NapCatActuator

pytestmark = pytest.mark.asyncio


@pytest.fixture(name="version")
def mock_qq(context: AppContext) -> str:
    os.makedirs(os.path.dirname(os.path.join(context.settings.qq.dir.base, context.qq_actuator.package_json_path)), exist_ok=True)
    os.makedirs(context.settings.qq.dir.config, exist_ok=True)
    version = "3.0.0-00001"

    with open(os.path.join(context.settings.qq.dir.base, context.qq_actuator.package_json_path), "w", encoding="utf-8") as f:
        f.write(json.dumps({"version": version}))

    return version


async def test_get_status_not_installed(authed_client: AsyncClient) -> None:
    response = await authed_client.get("/qq/status")
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0
    assert result["data"]["installed"] is False
    assert result["data"]["version"] is None


async def test_get_status_installed(authed_client: AsyncClient, version: str) -> None:
    response = await authed_client.get("/qq/status")
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0
    assert result["data"]["installed"] is True
    assert result["data"]["version"] == version


async def test_get_settings(authed_client: AsyncClient) -> None:
    response = await authed_client.get("/qq/settings")
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0
    assert "dir" in result["data"]
    assert "base" in result["data"]["dir"]
    assert "config" in result["data"]["dir"]


async def test_update_settings(authed_client: AsyncClient) -> None:
    payload = {
        "dir": {
            "base": "/new/data/path",
            "config": "/new/config/path",
        }
    }

    response = await authed_client.patch("/qq/settings", json=payload)
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0


@pytest.mark.parametrize("payload", [
    pytest.param(
        {"dir": {"base": "/new/base/path"}},
        id="Missing field"
    ),
    pytest.param(
        {"dir": {"base": "/new/base/path", "config": "/new/config/path", "not_existed": "Not existed"}},
        id="Not existed nest field"
    ),
    pytest.param(
        {"dir": {"base": "/new/base/path", "config": "/new/config/path"}, "not_existed": "Not existed"},
        id="Not existed field"
    )
])
async def test_update_settings_invalid_payload(authed_client: AsyncClient, payload: dict) -> None:
    response = await authed_client.patch("/qq/settings", json=payload)
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -3


async def test_update_without_napcat_removed(authed_client: AsyncClient, monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setattr(NapCatActuator, "installed", PropertyMock(return_value=True))

    response = await authed_client.post("/qq/update")
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -5
    assert result["message"] == "NapCat not removed"


@pytest.mark.parametrize("payload", [
    pytest.param({"purge": "Invalid"}, id="Invalid type"),
    pytest.param({"not_existed": "Not existed"}, id="Not existed field")
])
async def test_remove_invalid_payload(authed_client: AsyncClient, payload: dict) -> None:
    response = await authed_client.post("/qq/remove", json=payload)
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -3


async def test_remove_without_qq_installed(authed_client: AsyncClient, monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setattr(QQActuator, "installed", PropertyMock(return_value=False))

    response = await authed_client.post("/qq/remove", json={"purge": True})
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -5
    assert result["message"] == "QQ not installed"


async def test_remove_without_napcat_removed(authed_client: AsyncClient, monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setattr(QQActuator, "installed", PropertyMock(return_value=True))
    monkeypatch.setattr(NapCatActuator, "installed", PropertyMock(return_value=True))

    response = await authed_client.post("/qq/remove", json={"purge": True})
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -5
    assert result["message"] == "NapCat not removed"
