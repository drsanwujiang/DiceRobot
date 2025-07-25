from typing import Any

from fastapi.responses import Response as Response_, JSONResponse as JSONResponse_
from sse_starlette import EventSourceResponse as EventSourceResponse_

from .version import VERSION

__all__ = [
    "EmptyResponse",
    "JSONResponse",
    "EventSourceResponse"
]

DEFAULT_HEADERS = {
    "Server": f"DiceRobot/{VERSION}"
}


class EmptyResponse(Response_):
    def __init__(self, status_code: int = 204) -> None:
        super().__init__(headers=DEFAULT_HEADERS, status_code=status_code)


class JSONResponse(JSONResponse_):
    headers = DEFAULT_HEADERS | {
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
    headers = DEFAULT_HEADERS | {
        "Content-Type": "text/event-stream; charset=utf-8",
        "Cache-Control": "no-cache",
        "Connection": "keep-alive"
    }

    def __init__(self, *args, **kwargs) -> None:
        super().__init__(headers=self.headers, *args, **kwargs)
