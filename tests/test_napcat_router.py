from unittest.mock import AsyncMock, MagicMock, PropertyMock
import os
import json

import pytest
from httpx import AsyncClient

from app.context import AppContext
from app.actuators.qq import QQActuator
from app.actuators.napcat import NapCatActuator

pytestmark = pytest.mark.asyncio


@pytest.fixture(name="version")
def mock_napcat(context: AppContext) -> str:
    os.makedirs(context.settings.napcat.dir.base, exist_ok=True)
    os.makedirs(context.settings.napcat.dir.config, exist_ok=True)
    version = "1.2.3"

    with open(os.path.join(context.settings.napcat.dir.base, "package.json"), "w", encoding="utf-8") as f:
        f.write(json.dumps({"version": version}))

    return version


async def test_get_status_not_installed(authed_client: AsyncClient, monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setattr(NapCatActuator, "check_running", AsyncMock(return_value=False))

    response = await authed_client.get("/napcat/status")
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0
    assert result["data"]["installed"] is False
    assert result["data"]["configured"] is False
    assert result["data"]["running"] is False
    assert result["data"]["version"] is None


async def test_get_status_installed(context: AppContext, authed_client: AsyncClient, monkeypatch: pytest.MonkeyPatch, version: str) -> None:
    monkeypatch.setattr(NapCatActuator, "check_running", AsyncMock(return_value=True))
    context.settings.napcat.account = 99999

    response = await authed_client.get("/napcat/status")
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0
    assert result["data"]["installed"] is True
    assert result["data"]["configured"] is True
    assert result["data"]["running"] is True
    assert result["data"]["version"] == version


async def test_get_settings(authed_client: AsyncClient) -> None:
    response = await authed_client.get("/napcat/settings")
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0
    assert "dir" in result["data"]
    assert "api" in result["data"]
    assert "account" in result["data"]


async def test_update_settings(context: AppContext, authed_client: AsyncClient) -> None:
    account = 99999
    port = 13579
    payload = {
        "account": account,
        "api": {
            "host": "0.0.0.0",
            "port": port
        }
    }

    response = await authed_client.patch("/napcat/settings", json=payload)
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0
    assert context.settings.napcat.account == account
    assert context.settings.napcat.api.port == port


@pytest.mark.parametrize("payload", [
    pytest.param(
        {"dir": {"base": "/new/base/path"}},
        id="Missing field"
    ),
    pytest.param(
        {"dir": {"base": "/new/base/path", "config": "/new/config/path", "logs": "/new/logs/path", "not_existed": "Not existed"}},
        id="Not existed nest field"
    ),
    pytest.param(
        {"dir": {"base": "/new/base/path", "config": "/new/config/path", "logs": "/new/logs/path"}, "not_existed": "Not existed"},
        id="Not existed field"
    ),
    pytest.param(
        {"api": {"host": "not_a_valid_ip", "port": 13579}},
        id="Invalid API host"
    ),
    pytest.param(
        {"api": {"host": "0.0.0.0", "port": 0}},
        id="Invalid API port"
    ),
    pytest.param(
        {"account": 1234},
        id="Invalid account"
    ),
    pytest.param(
        {"autostart": "not_a_boolean"},
        id="Invalid autostart type"
    ),
    pytest.param(
        {},
        id="Empty payload"
    )
])
async def test_update_settings_invalid_payload(authed_client: AsyncClient, payload: dict) -> None:
    response = await authed_client.patch("/napcat/settings", json=payload)
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -3


async def test_update_without_qq_installed(authed_client: AsyncClient, monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setattr(QQActuator, "installed", PropertyMock(return_value=False))

    response = await authed_client.post("/napcat/update")
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -5
    assert result["message"] == "QQ not installed"


async def test_update_without_napcat_stopped(authed_client: AsyncClient, monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setattr(QQActuator, "installed", PropertyMock(return_value=True))
    monkeypatch.setattr(NapCatActuator, "check_running", AsyncMock(return_value=True))

    response = await authed_client.post("/napcat/update")
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -5
    assert result["message"] == "NapCat not stopped"


async def test_remove_without_napcat_installed(authed_client: AsyncClient, monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setattr(NapCatActuator, "installed", PropertyMock(return_value=False))

    response = await authed_client.post("/napcat/remove")
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -5
    assert result["message"] == "NapCat not installed"


async def test_remove_without_napcat_stopped(authed_client: AsyncClient, monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setattr(NapCatActuator, "installed", PropertyMock(return_value=True))
    monkeypatch.setattr(NapCatActuator, "check_running", AsyncMock(return_value=True))

    response = await authed_client.post("/napcat/remove")
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -5
    assert result["message"] == "NapCat not stopped"


async def test_start_without_qq_installed(authed_client: AsyncClient, monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setattr(QQActuator, "installed", PropertyMock(return_value=False))

    response = await authed_client.post("/napcat/start")
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -5
    assert result["message"] == "QQ not installed"


async def test_start_without_napcat_installed(authed_client: AsyncClient, monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setattr(QQActuator, "installed", PropertyMock(return_value=True))
    monkeypatch.setattr(NapCatActuator, "installed", PropertyMock(return_value=False))

    response = await authed_client.post("/napcat/start")
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -5
    assert result["message"] == "NapCat not installed"


async def test_start_without_napcat_configured(authed_client: AsyncClient, monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setattr(QQActuator, "installed", PropertyMock(return_value=True))
    monkeypatch.setattr(NapCatActuator, "installed", PropertyMock(return_value=True))
    monkeypatch.setattr(NapCatActuator, "configured", PropertyMock(return_value=False))

    response = await authed_client.post("/napcat/start")
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -5
    assert result["message"] == "NapCat not configured"


async def test_start_already_running(authed_client: AsyncClient, monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setattr(QQActuator, "installed", PropertyMock(return_value=True))
    monkeypatch.setattr(NapCatActuator, "installed", PropertyMock(return_value=True))
    monkeypatch.setattr(NapCatActuator, "configured", PropertyMock(return_value=True))
    monkeypatch.setattr(NapCatActuator, "check_running", AsyncMock(return_value=True))

    response = await authed_client.post("/napcat/start")
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -5
    assert result["message"] == "NapCat already running"


async def test_stop_not_running(authed_client: AsyncClient, monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setattr(NapCatActuator, "check_running", AsyncMock(return_value=False))

    response = await authed_client.post("/napcat/stop")
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -5
    assert result["message"] == "NapCat not running"
