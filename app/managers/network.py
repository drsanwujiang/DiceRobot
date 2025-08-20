from typing import TYPE_CHECKING

from ..network.cloud import CloudService
from ..network.napcat import NapCatService
from . import Manager

if TYPE_CHECKING:
    from ..context import AppContext

__all__ = [
    "NetworkManager"
]


class NetworkManager(Manager):
    def __init__(self, context: "AppContext"):
        super().__init__(context)

        self.napcat = NapCatService(context)
        self.cloud = CloudService(context)
