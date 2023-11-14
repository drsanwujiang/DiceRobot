from fastapi import FastAPI
from fastapi.responses import JSONResponse

from ..version import VERSION
from ..log import logger


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

    code: int = 0
    message: str = "Success"
    data: dict | list = None


def init_routers(app: FastAPI) -> None:
    logger.info("Initializing routers")

    from .report import router as report
    from .panel import router as panel

    app.include_router(report)
    app.include_router(panel)

    logger.info("Routers initialized")
