from typing import Type
import importlib
import pkgutil
import re

from plugin import DiceRobotPlugin, OrderPlugin, EventPlugin
from .log import logger
from .config import status, plugin_settings
from .exceptions import DiceRobotException
from .models.message import MessageChain
from .models.event import Event


class Dispatcher:
    order_pattern = re.compile(r"^\s*[.\u3002]\s*([\S\s]+)$")

    def __init__(self):
        self.order_plugins: dict[str, Type[OrderPlugin]] = {}
        self.event_plugins: dict[str, Type[EventPlugin]] = {}
        self.orders: dict[int, list[dict[str, re.Pattern | str]]] = {}
        self.events: dict[str, list[str]] = {}

    def load_plugins(self) -> None:
        package = importlib.import_module("plugin")

        for _, name, _ in pkgutil.walk_packages(package.__path__):
            try:
                importlib.import_module(f"{package.__name__}.{name}")
            except ModuleNotFoundError:
                continue

        for plugin in OrderPlugin.__subclasses__():
            if hasattr(plugin, "name") and isinstance(plugin.name, str):
                self.order_plugins[plugin.name] = plugin

        for plugin in EventPlugin.__subclasses__():
            if hasattr(plugin, "name") and isinstance(plugin.name, str):
                self.event_plugins[plugin.name] = plugin

        for plugin in list(self.order_plugins.values()) + list(self.event_plugins.values()):
            status.plugins[plugin.name] = status.Plugin(
                display_name=plugin.display_name,
                description=plugin.description,
                version=plugin.version
            )
            plugin.load()
            plugin.initialize()

        logger.info(f"{len(self.order_plugins)} order plugins and {len(self.event_plugins)} event plugins loaded")

    def load_orders_and_events(self) -> None:
        orders = {}

        for plugin_name, plugin in self.order_plugins.items():
            if hasattr(plugin, "orders") and hasattr(plugin, "priority"):
                if isinstance(plugin.priority, int) and plugin.priority not in orders:
                    orders[plugin.priority] = []

                plugin_orders = plugin.orders if isinstance(plugin.orders, list) else [plugin.orders]

                for order in plugin_orders:
                    if isinstance(order, str) and order:
                        orders[plugin.priority].append({
                            "pattern": re.compile(fr"^({order})\s*([\S\s]*)$"),
                            "name": plugin_name
                        })

        events = {}

        for plugin_name, plugin in self.event_plugins.items():
            if hasattr(plugin, "events"):
                plugin_events: list = plugin.events if isinstance(plugin.events, list) else [plugin.events]

                for event in plugin_events:
                    if issubclass(event, Event):
                        if event not in events:
                            events[event.__name__] = []

                        events[event.__name__].append(plugin_name)

        self.orders = dict(sorted(orders.items(), reverse=True))
        self.events = events

        logger.info(f"{sum(len(orders) for orders in self.orders.values())} orders and {len(self.events)} events loaded")

    def find_plugin(self, plugin_name: str) -> Type[DiceRobotPlugin] | None:
        return self.order_plugins.get(plugin_name) or self.event_plugins.get(plugin_name)

    def dispatch_order(self, message_chain: MessageChain, message_content: str) -> None:
        match = Dispatcher.order_pattern.fullmatch(message_content)

        if not match:
            raise RuntimeError("Dispatch missed")

        order_and_content = match.group(1)
        plugin_name, order, order_content = self.match_plugin(order_and_content)

        if not plugin_name:
            raise RuntimeError("Plugin match missed")
        elif not plugin_settings.get(plugin=plugin_name)["enabled"]:
            logger.info("Plugin disabled, execution skipped")
            return

        logger.info(f"Dispatch to plugin {plugin_name}")

        plugin_class = self.order_plugins[plugin_name]

        try:
            plugin = plugin_class(message_chain, order.lower(), order_content)

            if not plugin.check_enabled():
                logger.info("Chat disabled, execution skipped")
                return

            plugin()
        except DiceRobotException as e:
            plugin_class.reply_to_message_sender(message_chain, e.reply)
        except Exception as e:
            logger.exception(
                f"{e.__class__.__name__} occurred while dispatching plugin {plugin_name} to handle {message_chain.__class__.__name__}"
            )

    def match_plugin(self, order_and_content: str) -> tuple[str | None, str | None, str | None]:
        for priority, orders in self.orders.items():
            for pattern_and_name in orders:
                if match := pattern_and_name["pattern"].fullmatch(order_and_content):
                    return pattern_and_name["name"], match.group(1), match.group(2)

        return None, None, None

    def dispatch_event(self, event: Event) -> None:
        if event.__class__.__name__ not in self.events:
            logger.debug("Dispatch missed")
            raise RuntimeError("Dispatch missed")

        for plugin_name in self.events[event.__class__.__name__]:
            try:
                self.event_plugins[plugin_name](event)()
            except DiceRobotException as e:
                logger.error(
                    f"{e.__class__.__name__} occurred while dispatching plugin {plugin_name} to handle {event.__class__.__name__}"
                )
            except Exception as e:
                logger.exception(f"{e.__class__.__name__} occurred while dispatching plugin {plugin_name} to handle {event.__class__.__name__}")


dispatcher = Dispatcher()


def init_dispatcher() -> None:
    dispatcher.load_plugins()
    dispatcher.load_orders_and_events()

    logger.info("Dispatcher initialized")
