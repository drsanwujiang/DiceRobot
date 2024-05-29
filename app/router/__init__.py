from fastapi import FastAPI
from fastapi.responses import JSONResponse

from ..version import VERSION


class Response(JSONResponse):
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

        super().__init__(
            status_code=status_code,
            content=content
        )


def init_router(app: FastAPI) -> None:
    from .webhook import router as webhook
    from .admin import router as admin

    app.include_router(webhook)
    app.include_router(admin)
