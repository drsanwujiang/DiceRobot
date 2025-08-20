from typing import Any
from abc import ABC, abstractmethod

from ..enum import DataType

__all__ = [
    "DataHandler"
]


class DataHandler(ABC):
    type: DataType

    @abstractmethod
    async def load_file(self, filepath: str) -> Any | None:
        ...
