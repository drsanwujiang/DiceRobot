from typing import TYPE_CHECKING

if TYPE_CHECKING:
    from ..context import AppContext


class Manager:
    def __init__(self, context: "AppContext") -> None:
        self.context = context

    async def initialize(self) -> None:
        ...

    async def cleanup(self) -> None:
        ...
