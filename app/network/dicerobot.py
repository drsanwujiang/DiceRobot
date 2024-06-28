import uuid

from . import client

__all__ = [
    "download_qq",
    "download_napcat"
]


def download_qq() -> str:
    response = client.get("https://dl.drsanwujiang.com/dicerobot/qq.deb")
    file = f"/tmp/qq-{uuid.uuid4().hex}.deb"

    with open(file, "wb") as f:
        f.write(response.content)

    return file


def download_napcat() -> str:
    response = client.get("https://dl.drsanwujiang.com/dicerobot/napcat.zip")
    file = f"/tmp/napcat-{uuid.uuid4().hex}.zip"

    with open(file, "wb") as f:
        f.write(response.content)

    return file
