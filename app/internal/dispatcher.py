from typing import Type
import importlib
import pkgutil
import re
import copy

from plugins import DiceRobotPlugin, OrderPlugin, EventPlugin
from ..log import logger
from ..config import plugin_settings, replies
from ..exceptions import DiceRobotException
from .message import MessageChain
from .event import Event
from .util import reply_to_sender


class Dispatcher:
    order_pattern = re.compile(r"^\s*[.\u3002]\s*([\S\s]+)$")

    def __init__(self):
        self.order_plugins: dict[str, Type[OrderPlugin]] = {}
        self.event_plugins: dict[str, Type[EventPlugin]] = {}
        self.orders: dict[int, dict[str, dict[str, re.Pattern | str]]] = {}
        self.events: dict[str, list[str]] = {}

    def load_plugins(self) -> None:
        logger.info("Loading plugins")

        package = importlib.import_module("plugins")

        for _, name, _ in pkgutil.walk_packages(package.__path__):
            try:
                importlib.import_module(f"{package.__name__}.{name}")
            except ModuleNotFoundError:
                continue

        for plugin in OrderPlugin.__subclasses__():
            if hasattr(plugin, "name") and isinstance(plugin.name, str):
                self.order_plugins[plugin.name] = plugin
                self.load_defaults(plugin)

        for plugin in EventPlugin.__subclasses__():
            if hasattr(plugin, "name") and isinstance(plugin.name, str):
                self.event_plugins[plugin.name] = plugin
                self.load_defaults(plugin)

        logger.success(f"{len(self.order_plugins)} order plugins and {len(self.event_plugins)} event plugins loaded")

    @staticmethod
    def load_defaults(plugin: Type[DiceRobotPlugin]) -> None:
        # Load default plugin settings, replies
        if plugin.name not in plugin_settings and plugin.default_settings:
            plugin_settings[plugin.name] = copy.deepcopy(plugin.default_settings)
        else:
            for key, value in plugin.default_settings.items():
                plugin_settings[plugin.name].setdefault(key, value)

        if plugin.name not in replies and plugin.default_replies:
            replies[plugin.name] = copy.deepcopy(plugin.default_replies)
        else:
            for key, value in plugin.default_replies.items():
                replies[plugin.name].setdefault(key, value)

    def load_orders_and_events(self) -> None:
        logger.info("Loading orders and events")

        orders = {}

        for plugin_name, plugin in self.order_plugins.items():
            if hasattr(plugin, "orders") and hasattr(plugin, "priority"):
                if isinstance(plugin.priority, int) and plugin.priority not in orders:
                    orders[plugin.priority] = {}

                plugin_orders = plugin.orders if isinstance(plugin.orders, list) else [plugin.orders]

                for order in plugin_orders:
                    if isinstance(order, str):
                        orders[plugin.priority][order] = {
                            "pattern": re.compile(fr"^{order}\s*([\S\s]*)$"),
                            "name": plugin_name
                        }

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

        logger.success(f"{sum(len(orders) for orders in self.orders.values())} orders and {len(self.events)} events loaded")

    def dispatch_order(self, message_chain: MessageChain, message_content: str) -> bool:
        match = Dispatcher.order_pattern.fullmatch(message_content)

        if not match:
            return False

        order_and_content = match.group(1)
        plugin_name, order, order_content = self._parse_order(order_and_content)

        if not plugin_name:
            return False

        logger.info(f"Dispatch to plugin {plugin_name}")

        try:
            plugin = self.order_plugins[plugin_name](message_chain, order.lower(), order_content)

            if plugin.check_enabled():
                plugin()
        except DiceRobotException as e:
            reply_to_sender(message_chain, e.reply)

            logger.info(
                f"{e.__class__.__name__} occurred while dispatching plugin {plugin_name} to handle {message_chain.__class__.__name__}"
            )
        except Exception as e:
            logger.exception(
                f"{e.__class__.__name__} occurred while dispatching plugin {plugin_name} to handle {message_chain.__class__.__name__}"
            )

        return True

    def _parse_order(self, order_and_content: str) -> tuple[str | None, str | None, str | None]:
        for priority, orders in self.orders.items():
            for order, pattern_and_name in orders.items():
                if match := pattern_and_name["pattern"].fullmatch(order_and_content):
                    return pattern_and_name["name"], order, match.group(1)

        return None, None, None

    def dispatch_event(self, event: Event) -> bool:
        if event.__class__.__name__ not in self.events:
            return False

        for plugin_name in self.events[event.__class__.__name__]:
            try:
                self.event_plugins[plugin_name](event)()
            except DiceRobotException as e:
                logger.error(
                    f"{e.__class__.__name__} occurred while dispatching plugin {plugin_name} to handle {event.__class__.__name__}"
                )
            except Exception as e:
                logger.exception(f"{e.__class__.__name__} occurred while dispatching plugin {plugin_name} to handle {event.__class__.__name__}")

        return True


dispatcher = Dispatcher()


def init_dispatcher():
    logger.info("Initializing dispatcher")

    dispatcher.load_plugins()
    dispatcher.load_orders_and_events()

    logger.info("Dispatcher initialized")
