from unittest.mock import AsyncMock
import os
import shutil
from collections.abc import AsyncGenerator

import pytest
import pytest_asyncio
from httpx import AsyncClient, Request

from app.context import AppContext
from app.auth import Auth
from app.enum import ApplicationStatus, Role
from app.exceptions import OrderSuspiciousError, OrderRepetitionExceededError, OrderError
from . import build_group_message, build_private_message

pytestmark = pytest.mark.asyncio


@pytest_asyncio.fixture(autouse=True)
async def prepare_data(context: AppContext) -> None:
    shutil.copytree(f"{os.getcwd()}/data", context.settings.app.dir.data)
    await context.data_manager.initialize()


@pytest_asyncio.fixture(autouse=True)
async def prepare_plugins(context: AppContext) -> None:
    context.task_manager = AsyncMock()
    await context.dispatch_manager.load_plugins()
    context.dispatch_manager.load_orders_and_events()


@pytest.fixture(autouse=True)
def prepare_plugin_settings(context: AppContext) -> None:
    context.plugin_settings.set(plugin="dicerobot.chat", settings={
        "base_url": os.environ.get("TEST_AI_BASE_URL"),
        "api_key": os.environ.get("TEST_AI_API_KEY"),
        "model": os.environ.get("TEST_AI_MODEL")
    })


@pytest.fixture(autouse=True)
def set_app_status(context: AppContext) -> None:
    context.status.app = ApplicationStatus.RUNNING


@pytest_asyncio.fixture
async def webhook_client(context: AppContext, authed_client: AsyncClient) -> AsyncGenerator[AsyncClient]:
    auth = Auth(context)

    async def handle_request(request: Request) -> None:
        body = await request.aread()
        digest = auth.calculate_signature("sha1", body)
        headers = {"X-Signature": f"sha1={digest}"}
        request.headers.update(headers)

    authed_client.event_hooks["request"].append(handle_request)
    yield authed_client
    authed_client.event_hooks["request"].remove(handle_request)


@pytest.mark.parametrize("order", [
    pytest.param(".bot", id="Default order"),
    pytest.param(".bot about", id="Explicit order")
])
async def test_bot_info(context: AppContext, webhook_client: AsyncClient, order: str) -> None:
    response = await webhook_client.post("/report", json=build_group_message(order).model_dump())
    assert response.status_code == 204
    context.network_manager.napcat.send_group_message.assert_called_once()


async def test_bot_off(context: AppContext, webhook_client: AsyncClient) -> None:
    message = build_group_message(".bot off")
    message.sender.role = Role.ADMIN

    response = await webhook_client.post("/report", json=message.model_dump())
    assert response.status_code == 204
    context.network_manager.napcat.send_group_message.assert_called_once()

    message = build_group_message(".bot")
    message.sender.role = Role.MEMBER

    response = await webhook_client.post("/report", json=message.model_dump())
    assert response.status_code == 204
    context.network_manager.napcat.send_group_message.assert_called_once()


async def test_bot_on(context: AppContext, webhook_client: AsyncClient) -> None:
    message = build_group_message(".bot on")
    message.sender.role = Role.ADMIN

    response = await webhook_client.post("/report", json=message.model_dump())
    assert response.status_code == 204
    context.network_manager.napcat.send_group_message.assert_called_once()

    message = build_group_message(".bot")
    message.sender.role = Role.MEMBER

    response = await webhook_client.post("/report", json=message.model_dump())
    assert response.status_code == 204
    context.network_manager.napcat.send_group_message.assert_called()


async def test_bot_nickname(context: AppContext, webhook_client: AsyncClient) -> None:
    message = build_group_message(".bot nickname Adam")
    message.sender.role = Role.ADMIN

    response = await webhook_client.post("/report", json=message.model_dump())
    assert response.status_code == 204
    context.network_manager.napcat.set_group_card.assert_called_once_with(message.group_id, message.self_id, "Adam")
    context.network_manager.napcat.send_group_message.assert_called_once()


@pytest.mark.parametrize("order", [
    pytest.param(".bot off", id="Bot off"),
    pytest.param(".bot on", id="Bot on"),
    pytest.param(".bot nickname Adam", id="Bot nickname")
])
async def test_bot_without_permission(context: AppContext, webhook_client: AsyncClient, order: str) -> None:
    message = build_group_message(order)
    message.sender.role = Role.MEMBER

    with pytest.raises(OrderError):
        await webhook_client.post("/report", json=message.model_dump())

    context.network_manager.napcat.send_group_message.assert_called_once()


@pytest.mark.parametrize("order", [
    pytest.param(".r", id="Simplest order"),
    pytest.param(".rd", id="Simple order"),
    pytest.param(".rd100", id="Explicit surface"),
    pytest.param(".rd 50", id="Explicit surface with space"),
    pytest.param(".r10d100", id="Explicit count and surface"),
    pytest.param(".r10d100k2", id="Explicit count, surface and keep"),
    pytest.param(".r10d100k2#3", id="Explicit count, surface, keep and repetition"),
    pytest.param(".r10d100k2x2", id="Explicit count, surface, keep and multiply"),
    pytest.param(".r10d100k2x2#3", id="With reason and repetition"),
    pytest.param(".rd50Reason", id="Explicit surface with reason"),
    pytest.param(".rd50 Reason", id="Explicit surface with reason and space"),
    pytest.param(".rdReason", id="Simple order with reason only"),
    pytest.param(".r10d100k2x2 Some Reason", id="Explicit count, surface, keep and multiply with reason"),
    pytest.param(".r(5d100+d30+666)*5-2+6d50k2x2+6X5 Some Reason", id="Complex expression with reason"),
    pytest.param(".r(5d100+d30+666)*5-2+6d50k2x2+6X5 Some Reason #3", id="Complex expression with reason and repetition")
])
async def test_dice(context: AppContext, webhook_client: AsyncClient, order: str):
    response = await webhook_client.post("/report", json=build_group_message(order).model_dump())
    assert response.status_code == 204
    context.network_manager.napcat.send_group_message.assert_called_once()


@pytest.mark.parametrize("order", [
    pytest.param(".r0d", id="Zero count"),
    pytest.param(".r1001d100", id="Exceed max count"),
    pytest.param(".rd0", id="Zero surface"),
    pytest.param(".rd10001", id="Exceed max surface"),
    pytest.param(".rdk0", id="Zero keep"),
    pytest.param(".r10d100k11", id="Exceed max keep"),
    pytest.param(".r10d100kk2", id="Repeated symbol")
])
async def test_dice_invalid(context: AppContext, webhook_client: AsyncClient, order: str):
    with pytest.raises(OrderError):
        await webhook_client.post("/report", json=build_group_message(order).model_dump())

    context.network_manager.napcat.send_group_message.assert_called_once()


@pytest.mark.parametrize("order", [
    pytest.param(".rh", id="Simplest order"),
    pytest.param(".rhd", id="Simple order"),
    pytest.param(".rhd100", id="Explicit surface"),
    pytest.param(".rhd 50", id="Explicit surface with space"),
    pytest.param(".rh10d100", id="Explicit count and surface"),
    pytest.param(".rh10d100k2", id="Explicit count, surface and keep"),
    pytest.param(".rh10d100k2#3", id="Explicit count, surface, keep and repetition"),
    pytest.param(".rh10d100k2x2", id="Explicit count, surface, keep and multiply"),
    pytest.param(".rh10d100k2x2#3", id="With reason and repetition"),
    pytest.param(".rhd50Reason", id="Simple order with reason"),
    pytest.param(".rhd50 Reason", id="Simple order with reason and space"),
    pytest.param(".rhdReason", id="Simple order with reason only"),
    pytest.param(".rh10d100k2x2 Some Reason", id="Explicit count, surface, keep and multiply with reason"),
    pytest.param(".rh(5d100+d30+666)*5-2+6d50k2x2+6X5 Some Reason", id="Complex expression with reason"),
    pytest.param(".rh(5d100+d30+666)*5-2+6d50k2x2+6X5 Some Reason #3", id="Complex expression with reason and repetition")
])
async def test_hidden_dice(context: AppContext, webhook_client: AsyncClient, order: str) -> None:
    response = await webhook_client.post("/report", json=build_group_message(".rh").model_dump())
    assert response.status_code == 204
    context.network_manager.napcat.send_group_message.assert_called_once()
    context.network_manager.napcat.send_private_message.assert_called_once()


@pytest.mark.parametrize("order", [
    pytest.param(".rh0d", id="Zero count"),
    pytest.param(".rh1001d100", id="Exceed max count"),
    pytest.param(".rhd0", id="Zero surface"),
    pytest.param(".rhd10001", id="Exceed max surface"),
    pytest.param(".rhdk0", id="Zero keep"),
    pytest.param(".rh10d100k11", id="Exceed max keep"),
    pytest.param(".rh10d100kk2", id="Repeated symbol")
])
async def test_hidden_dice_invalid(context: AppContext, webhook_client: AsyncClient, order: str):
    with pytest.raises(OrderError):
        await webhook_client.post("/report", json=build_group_message(order).model_dump())

    context.network_manager.napcat.send_group_message.assert_called_once()


async def test_hidden_dice_not_in_group(context: AppContext, webhook_client: AsyncClient) -> None:
    with pytest.raises(OrderError):
        await webhook_client.post("/report", json=build_private_message(".rh").model_dump())

    context.network_manager.napcat.send_private_message.assert_called_once()


@pytest.mark.parametrize("order", [
    pytest.param(".rb", id="Simple bonus"),
    pytest.param(".rb2", id="Explicit bonus"),
    pytest.param(".rb2#5", id="Explicit bonus with repetition"),
    pytest.param(".rbReason", id="Simple bonus with reason"),
    pytest.param(".rb3 Reason", id="Explicit bonus with reason"),
    pytest.param(".rb3 Reason #5", id="Explicit bonus with reason and repetition"),
    pytest.param(".rp", id="Simple penalty"),
    pytest.param(".rp2", id="Explicit penalty"),
    pytest.param(".rp2#5", id="Explicit penalty with repetition"),
    pytest.param(".rpReason", id="Simple penalty with reason"),
    pytest.param(".rp3 Reason", id="Explicit penalty with reason"),
    pytest.param(".rp3 Reason #5", id="Explicit penalty with reason and repetition")
])
async def test_bonus_penalty_dice(context: AppContext, webhook_client: AsyncClient, order: str) -> None:
    response = await webhook_client.post("/report", json=build_group_message(order).model_dump())
    assert response.status_code == 204
    context.network_manager.napcat.send_group_message.assert_called_once()


@pytest.mark.parametrize("order", [
    pytest.param(".rb0", id="Zero bonus"),
    pytest.param(".rb999", id="Exceed max bonus"),
    pytest.param(".rp0", id="Zero penalty"),
    pytest.param(".rp999", id="Exceed max penalty")
])
async def test_bonus_penalty_dice_invalid(context: AppContext, webhook_client: AsyncClient, order: str) -> None:
    with pytest.raises(OrderError):
        await webhook_client.post("/report", json=build_group_message(order).model_dump())

    context.network_manager.napcat.send_group_message.assert_called_once()


@pytest.mark.parametrize("order", [
    pytest.param(".ra50", id="Explicit attribute/skill"),
    pytest.param(".ra75#3", id="Explicit attribute/skill with repetition"),
    pytest.param(".ra60 Reason", id="Explicit attribute/skill with reason"),
    pytest.param(".ra88 Reason #3", id="Explicit attribute/skill with reason and repetition")
])
async def test_skill_roll(context: AppContext, webhook_client: AsyncClient, order: str) -> None:
    response = await webhook_client.post("/report", json=build_group_message(order).model_dump())
    assert response.status_code == 204
    context.network_manager.napcat.send_group_message.assert_called_once()


@pytest.mark.parametrize("order", [
    pytest.param(".ra", id="No attribute/skill"),
    pytest.param(".ra0", id="Zero attribute/skill")
])
async def test_skill_roll_invalid(context: AppContext, webhook_client: AsyncClient, order: str) -> None:
    with pytest.raises(OrderError):
        await webhook_client.post("/report", json=build_group_message(order).model_dump())

    context.network_manager.napcat.send_group_message.assert_called_once()


@pytest.mark.parametrize("order", [
    pytest.param(".rule", id="Show current rule"),
    pytest.param(".rule coc7", id="Switch to another rule")
])
async def test_rule(context: AppContext, webhook_client: AsyncClient, order: str) -> None:
    response = await webhook_client.post("/report", json=build_group_message(order).model_dump())
    assert response.status_code == 204
    context.network_manager.napcat.send_group_message.assert_called_once()


@pytest.mark.parametrize("order", [
    pytest.param(".chat What is the meaning of life? Give me a short answer", id="Chat"),
    pytest.param(".think What is the meaning of life? Give me a short answer", id="Think")
])
async def test_chat(context: AppContext, webhook_client: AsyncClient, order: str) -> None:
    response = await webhook_client.post("/report", json=build_group_message(order).model_dump())
    assert response.status_code == 204
    context.network_manager.napcat.send_group_message.assert_called_once()


@pytest.mark.parametrize("order", [
    pytest.param(".rb99999", id="Suspicious bonus dice"),
    pytest.param(".rp99999", id="Suspicious penalty dice"),
    pytest.param(".ra100000", id="Suspicious skill roll")
])
async def test_suspicious(context: AppContext, webhook_client: AsyncClient, order: str) -> None:
    with pytest.raises(OrderSuspiciousError):
        await webhook_client.post("/report", json=build_group_message(order).model_dump())

    context.network_manager.napcat.send_group_message.assert_called_once()


@pytest.mark.parametrize("order", [
    pytest.param(".bot#2", id="Bot"),
    pytest.param(".r#31", id="Dice"),
    pytest.param(".rh#31", id="Hidden dice"),
    pytest.param(".rb#31", id="Bonus dice"),
    pytest.param(".rp#31", id="Penalty dice"),
    pytest.param(".ra#31", id="Skill roll"),
    pytest.param(".rule#2", id="Rule"),
    pytest.param(".chat What is the meaning of life? #2", id="Chat"),
    pytest.param(".think What is the meaning of life? #2", id="Think")
])
async def test_repetition_exceeded(context: AppContext, webhook_client: AsyncClient, order: str) -> None:
    with pytest.raises(OrderRepetitionExceededError):
        await webhook_client.post("/report", json=build_group_message(order).model_dump())

    context.network_manager.napcat.send_group_message.assert_called_once()
