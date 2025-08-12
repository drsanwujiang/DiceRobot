from loguru import logger
from fastapi import FastAPI, Request, Response
from fastapi.exceptions import StarletteHTTPException, RequestValidationError

from .globals import DEBUG
from .exceptions import DiceRobotHTTPException
from .responses import JSONResponse

__all__ = [
    "init_exception_handlers"
]


async def http_exception_handler(_: Request, e: StarletteHTTPException) -> Response:
    return JSONResponse(
        status_code=e.status_code,
        code=e.status_code * -1,
        message=str(e.detail)
    )


async def request_validation_error_handler(_: Request, __: RequestValidationError) -> Response:
    return JSONResponse(
        status_code=400,
        code=-3,
        message="Invalid request"
    )


async def dicerobot_http_exception_handler(_: Request, e: DiceRobotHTTPException) -> Response:
    return JSONResponse(
        status_code=e.status_code,
        code=e.code,
        message=e.message
    )


async def exception_handler(_: Request, __: Exception) -> Response:
    logger.exception("Unexpected exception occurred")

    return JSONResponse(
        status_code=500,
        code=-500,
        message="Internal server error"
    )


def init_exception_handlers(app: FastAPI) -> None:
    app.add_exception_handler(StarletteHTTPException, http_exception_handler)  # type: ignore
    app.add_exception_handler(RequestValidationError, request_validation_error_handler)  # type: ignore
    app.add_exception_handler(DiceRobotHTTPException, dicerobot_http_exception_handler)  # type: ignore

    if not DEBUG:
        app.add_exception_handler(Exception, exception_handler)
