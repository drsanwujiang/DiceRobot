from typing import Any

from fastapi import FastAPI
from fastapi.responses import Response as Response_, JSONResponse as JSONResponse_
from sse_starlette import EventSourceResponse as EventSourceResponse_

from ..version import VERSION

default_headers = {
    "Access-Control-Allow-Origin": "*",
    "Access-Control-Allow-Methods": "GET, POST, PATCH, PUT, DELETE, OPTIONS",
    "Access-Control-Allow-Headers": "Content-Type, Authorization",
    "Server": f"DiceRobot/{VERSION}"
}


class EmptyResponse(Response_):
    def __init__(self, status_code: int = 204):
        super().__init__(headers=default_headers, status_code=status_code)


class JSONResponse(JSONResponse_):
    headers = default_headers | {
        "Content-Type": "application/json; charset=utf-8"
    }

    def __init__(
        self,
        status_code: int = 200,
        code: int = 0,
        message: str = "Success",
        data: Any = None
    ) -> None:
        content = {
            "code": code,
            "message": message
        }

        if data is not None:
            content["data"] = data

        super().__init__(headers=self.headers, status_code=status_code, content=content)


class EventSourceResponse(EventSourceResponse_):
    headers = default_headers | {
        "Content-Type": "text/event-stream; charset=utf-8"
    }

    def __init__(self, *args, **kwargs) -> None:
        super().__init__(headers=self.headers, *args, **kwargs)


def init_router(app: FastAPI) -> None:
    from .webhook import router as webhook
    from .admin import router as admin
    from .qq import router as qq
    from .napcat import router as napcat

    app.include_router(webhook)
    app.include_router(admin)
    app.include_router(qq)
    app.include_router(napcat)
