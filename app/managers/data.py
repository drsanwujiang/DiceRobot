from typing import TYPE_CHECKING, Any
import os

from loguru import logger

from ..enum import DataType
from ..data import DataHandler
from ..data.rule import RuleHandler
from . import Manager

if TYPE_CHECKING:
    from ..context import AppContext

__all__ = [
    "DataManager"
]


class DataManager(Manager):
    def __init__(self, context: "AppContext") -> None:
        super().__init__(context)

        self._handlers: dict[DataType, DataHandler] = {}
        self._registry: dict[DataType, dict[str, Any]] = {}

    async def initialize(self) -> None:
        self._ensure_directory()
        self.register_handler(RuleHandler())
        # self.register_handler(DeckHandler())
        await self.load_data()
        logger.debug("Data manager initialized")

    def _ensure_directory(self) -> None:
        os.makedirs(self.context.settings.app.dir.data, exist_ok=True)

        for type_ in DataType.__members__.values():
            os.makedirs(os.path.join(self.context.settings.app.dir.data, type_.name), exist_ok=True)

    def register_handler(self, handler: DataHandler) -> None:
        self._handlers[handler.type] = handler
        self._registry[handler.type] = {}
        logger.debug(f"Data handler for type \"{handler.type.value}\" registered")

    async def load_data(self):
        for type_ in DataType.__members__.values():
            if type_ in self._handlers:
                await self._load_from_handler(self._handlers[type_])

    async def _load_from_handler(self, handler: DataHandler):
        dir_path = os.path.join(self.context.settings.app.dir.data, handler.type.value)

        for filename in os.listdir(dir_path):
            if filename.endswith(".json") and (data := await handler.load_file(os.path.join(dir_path, filename))):
                self._registry[handler.type][data.id] = data
                logger.debug(f"Data loaded, type: {handler.type.value}, id: {data.id}")

    def get_data(self, data_type: DataType, data_id: str) -> Any | None:
        return self._registry.get(data_type, {}).get(data_id)

    def list_data(self, data_type: DataType) -> list[str]:
        return list(self._registry.get(data_type, {}).keys())

    def get_rule(self, rule_id: str) -> Any | None:
        return self.get_data(DataType.RULE, rule_id)

    def list_rules(self) -> list[str]:
        return self.list_data(DataType.RULE)

    def get_deck(self, deck_id: str) -> Any | None:
        return self.get_data(DataType.DECK, deck_id)

    def list_decks(self) -> list[str]:
        return self.list_data(DataType.DECK)
