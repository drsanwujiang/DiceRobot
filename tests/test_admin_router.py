from typing import Any
from unittest.mock import AsyncMock

import pytest
import pytest_asyncio
from httpx import AsyncClient

from app.context import AppContext
from app.enum import ApplicationStatus, ChatType

pytestmark = pytest.mark.asyncio


@pytest_asyncio.fixture(name="plugin")
async def mock_plugin(context: AppContext) -> str:
    context.task_manager = AsyncMock()
    await context.dispatch_manager.load_plugins()
    return "dicerobot.dice"


@pytest_asyncio.fixture(name="chat", params=[
    pytest.param((ChatType.FRIEND, 88888), id="Friend chat"),
    pytest.param((ChatType.GROUP, 12345), id="Group chat")
])
async def mock_chat(context: AppContext, request: Any) -> tuple[ChatType, int]:
    chat_type, chat_id = request.param
    context.chat_settings.set(chat_type=chat_type, chat_id=chat_id, settings_group="dicerobot", settings={
        "enabled": True
    })
    return request.param


async def test_get_application_status(context: AppContext, authed_client: AsyncClient) -> None:
    context.status.app = ApplicationStatus.RUNNING

    response = await authed_client.get("/status")
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0
    assert result["data"]["app"] == 0


async def test_set_module_status(context: AppContext, authed_client: AsyncClient) -> None:
    payload = {
        "order": False,
        "event": False
    }

    response = await authed_client.post("/status/module", json=payload)
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0
    assert context.status.module.order is False
    assert context.status.module.event is False


@pytest.mark.parametrize("payload", [
    pytest.param(
        {"order": False},
        id="Missing field"
    ),
    pytest.param(
        {"order": "Invalid", "event": False},
        id="Invalid type"
    )
])
async def test_set_module_status_invalid_payload(authed_client: AsyncClient, payload: dict) -> None:
    response = await authed_client.post("/status/module", json=payload)
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -3


async def test_update_security_settings(authed_client: AsyncClient) -> None:
    payload = {
        "admin": {
            "password": "New_password"
        }
    }

    response = await authed_client.patch("/settings/security", json=payload)
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0


@pytest.mark.parametrize("payload", [
    pytest.param({"admin": {"not_existed": "Not existed"}}, id="Not existed nest field"),
    pytest.param({"not_existed": "Not existed"}, id="Not existed field")
])
async def test_update_security_settings_invalid_payload(authed_client: AsyncClient, payload: dict) -> None:
    response = await authed_client.patch("/settings/security", json=payload)
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -3


async def test_get_application_settings(authed_client: AsyncClient) -> None:
    response = await authed_client.get("/settings")
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0
    assert "dir" in result["data"]
    assert "base" in result["data"]["dir"]
    assert "logs" in result["data"]["dir"]
    assert "data" in result["data"]["dir"]
    assert "temp" in result["data"]["dir"]


async def test_update_application_settings(authed_client: AsyncClient) -> None:
    payload = {
        "dir": {
            "data": "/new/data/path",
            "temp": "/new/temp/path"
        }
    }

    response = await authed_client.patch("/settings", json=payload)
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0


@pytest.mark.parametrize("payload", [
    pytest.param(
        {"dir": {"data": "/new/data/path"}},
        id="Missing field"
    ),
    pytest.param(
        {"dir": {"data": "/new/data/path", "temp": "/new/temp/path", "logs": "/new/logs/path"}},
        id="Not allowed field"
    ),
    pytest.param(
        {"dir": {"data": "/new/data/path", "temp": "/new/temp/path", "not_existed": "Not existed"}},
        id="Not existed nest field"
    ),
    pytest.param(
        {"dir": {"data": "/new/data/path", "temp": "/new/temp/path"}, "not_existed": "Not existed"},
        id="Not existed field"
    )
])
async def test_update_application_settings_invalid_payload(authed_client: AsyncClient, payload: dict) -> None:
    response = await authed_client.patch("/settings", json=payload)
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -3


async def test_get_plugin_list(authed_client: AsyncClient, plugin: str) -> None:
    response = await authed_client.get("/plugins")
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0
    assert plugin in result["data"]


async def test_get_plugin(authed_client: AsyncClient, plugin: str) -> None:
    response = await authed_client.get(f"/plugin/{plugin}")
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0
    assert "display_name" in result["data"]
    assert "description" in result["data"]
    assert "version" in result["data"]
    assert "priority" in result["data"]
    assert "orders" in result["data"]


async def test_get_plugin_settings(authed_client: AsyncClient, plugin: str) -> None:
    response = await authed_client.get(f"/plugin/{plugin}/settings")
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0
    assert result["data"]["enabled"] is True


async def test_update_plugin_settings(authed_client: AsyncClient, plugin: str) -> None:
    payload = {
        "enabled": False
    }

    response = await authed_client.patch(f"/plugin/{plugin}/settings", json=payload)
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0


@pytest.mark.parametrize("payload", [
    pytest.param({"max_count": 100}, id="Missing field"),
    pytest.param({"enabled": False, "not_existed": "Not existed"}, id="Not existed field")
])
async def test_update_plugin_settings_invalid_payload(authed_client: AsyncClient, plugin: str, payload: dict) -> None:
    response = await authed_client.patch(f"/plugin/{plugin}/settings", json=payload)
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -3


async def test_reset_plugin_settings(authed_client: AsyncClient, plugin: str) -> None:
    response = await authed_client.post(f"/plugin/{plugin}/settings/reset")
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0


async def test_get_plugin_replies(authed_client: AsyncClient, plugin: str) -> None:
    response = await authed_client.get(f"/plugin/{plugin}/replies")
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0
    assert isinstance(result["data"], dict)
    assert len(result["data"]) > 0


async def test_update_plugin_replies(authed_client: AsyncClient, plugin: str) -> None:
    payload = {
        "result": "Result"
    }

    response = await authed_client.patch(f"/plugin/{plugin}/replies", json=payload)
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0


@pytest.mark.parametrize("payload", [
    pytest.param({"not_existed": "Not existed"}, id="Not existed field")
])
async def test_update_plugin_replies_invalid_payload(authed_client: AsyncClient, plugin: str, payload: dict) -> None:
    response = await authed_client.patch(f"/plugin/{plugin}/replies", json=payload)
    assert response.status_code == 400
    result = response.json()
    assert result["code"] == -3


async def test_reset_plugin_replies(authed_client: AsyncClient, plugin: str) -> None:
    response = await authed_client.post(f"/plugin/{plugin}/replies/reset")
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0


async def test_get_chat_settings(authed_client: AsyncClient, chat: tuple[ChatType, int]) -> None:
    chat_type, chat_id = chat

    response = await authed_client.get(f"/chat/{chat_type.value}/{chat_id}/settings/dicerobot")
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0
    assert result["data"]["enabled"] is True

    response = await authed_client.get(f"/chat/{chat_type.value}/{chat_id}/settings/plugin")
    assert response.status_code == 200
    result = response.json()
    assert result["code"] == 0
    assert result["data"] == {}


@pytest.mark.parametrize("method, url, payload", [
    pytest.param("GET", "/plugin/not_exist", None, id="Get plugin"),
    pytest.param("GET", "/plugin/not_exist/settings", None, id="Get plugin settings"),
    pytest.param("PATCH", "/plugin/not_exist/settings", {"enabled": False}, id="Update plugin settings"),
    pytest.param("POST", "/plugin/not_exist/settings/reset", None, id="Reset plugin settings"),
    pytest.param("GET", "/plugin/not_exist/replies", None, id="Get plugin replies"),
    pytest.param("PATCH", "/plugin/not_exist/replies", {}, id="Update plugin replies"),
    pytest.param("POST", "/plugin/not_exist/replies/reset", None, id="Reset plugin replies")
])
async def test_plugin_not_found(authed_client: AsyncClient, method: str, url: str, payload: dict) -> None:
    response = await authed_client.request(method, url, json=payload)
    assert response.status_code == 404
    result = response.json()
    assert result["code"] == -4
