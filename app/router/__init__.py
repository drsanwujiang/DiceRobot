from fastapi import FastAPI
from fastapi.responses import Response as _Response, JSONResponse as _JSONResponse

from ..version import VERSION


class EmptyResponse(_Response):
    def __init__(self, status_code: int = 204):
        super().__init__(status_code=status_code)


class JSONResponse(_JSONResponse):
    headers = {
        "Access-Control-Allow-Origin": "*",
        "Access-Control-Allow-Methods": "GET, POST, PATCH, PUT, DELETE, OPTIONS",
        "Access-Control-Allow-Headers": "Content-Type, Authorization",
        "Server": f"DiceRobot/{VERSION}"
    }

    def __init__(
        self,
        status_code: int = 200,
        code: int = 0,
        message: str = "Success",
        data: dict | list = None
    ) -> None:
        content = {
            "code": code,
            "message": message
        }

        if data is not None:
            content["data"] = data

        super().__init__(status_code=status_code, content=content)


def init_router(app: FastAPI) -> None:
    from .webhook import router as webhook
    from .admin import router as admin
    from .qq import router as qq
    from .napcat import router as napcat

    app.include_router(webhook)
    app.include_router(admin)
    app.include_router(qq)
    app.include_router(napcat)
