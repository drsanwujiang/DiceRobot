import uuid

from . import client

__all__ = [
    "download_mirai"
]


def download_mirai() -> str:
    response = client.get("https://dl.drsanwujiang.com/dicerobot/mirai.zip")
    file = f"/tmp/mirai-{uuid.uuid4().hex}.zip"

    with open(file, "wb") as f:
        f.write(response.content)

    return file
