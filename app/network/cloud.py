from ..models.network.cloud import (
    GetVersionsResponse
)
from ..config import settings
from . import client

__all__ = [
    "get_versions"
]


async def get_versions() -> GetVersionsResponse:
    return GetVersionsResponse.model_validate((await client.get(
        settings.cloud.api.base_url + "/versions"
    )).json())
