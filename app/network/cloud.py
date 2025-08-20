from typing import TYPE_CHECKING

from ..models.network.cloud import (
    GetVersionsResponse
)

if TYPE_CHECKING:
    from ..context import AppContext

__all__ = [
    "CloudService"
]


class CloudService:
    def __init__(self, context: "AppContext") -> None:
        self.context = context

    async def get_versions(self) -> GetVersionsResponse:
        return GetVersionsResponse.model_validate((await self.context.http_client.get(
            self.context.settings.cloud.api.base_url + "/versions"
        )).json())
