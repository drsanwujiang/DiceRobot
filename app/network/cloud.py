from ..models.network.cloud import (
    GetVersionsResponse
)
from ..config import settings
from . import client

__all__ = [
    "get_versions"
]


def get_versions() -> GetVersionsResponse:
    return GetVersionsResponse.model_validate(client.get(
        settings.cloud.api.base_url + "/version"
    ).json())
