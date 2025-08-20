import pytest

from app.context import AppContext
from plugin import OrderPlugin

pytestmark = pytest.mark.asyncio


class FakePlugin(OrderPlugin):
    async def __call__(self) -> None:
        ...


class PluginA(FakePlugin):
    name = "fake.a"
    orders = "r"
    priority = 1


class PluginB(FakePlugin):
    name = "fake.b"
    orders = "ra"
    priority = 100


@pytest.fixture(autouse=True)
def prepare_plugins(context: AppContext) -> None:
    context.dispatch_manager.order_plugins = {
        "fake.a": PluginA,
        "fake.b": PluginB
    }
    context.dispatch_manager.load_orders_and_events()


async def test_match_plugin(context: AppContext) -> None:
    plugin_name, order, content = context.dispatch_manager.match_plugin("rabc")
    assert plugin_name == "fake.b"
    assert order == "ra"
    assert content == "bc"

    plugin_name, order, content = context.dispatch_manager.match_plugin("RB")
    assert plugin_name == "fake.a"
    assert order == "R"
    assert content == "B"

    plugin_name, _, _ = context.dispatch_manager.match_plugin("unknown")
    assert plugin_name is None
