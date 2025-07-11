from loguru import logger
from fastapi import FastAPI, Request, Response
from fastapi.responses import JSONResponse
from fastapi.exceptions import StarletteHTTPException as HTTPException, RequestValidationError

from .config import status
from .exceptions import DiceRobotHTTPException


async def http_exception_handler(_: Request, e: HTTPException) -> Response:
    return JSONResponse(
        status_code=e.status_code,
        content={
            "code": e.status_code * -1,
            "message": str(e.detail)
        }
    )


async def request_validation_error_handler(_: Request, __: RequestValidationError) -> Response:
    return JSONResponse(
        status_code=400,
        content={
            "code": -3,
            "message": "Invalid request"
        }
    )


async def dicerobot_http_exception_handler(_: Request, e: DiceRobotHTTPException) -> Response:
    return JSONResponse(
        status_code=e.status_code,
        content={"code": e.code, "message": e.message}
    )


async def exception_handler(_: Request, __: Exception) -> Response:
    logger.exception("Unexpected exception occurred")

    return JSONResponse(
        status_code=500,
        content={
            "code": -500,
            "message": "Internal server error"
        }
    )


def init_exception_handlers(app: FastAPI) -> None:
    app.add_exception_handler(HTTPException, http_exception_handler)  # type: ignore
    app.add_exception_handler(RequestValidationError, request_validation_error_handler)  # type: ignore
    app.add_exception_handler(DiceRobotHTTPException, dicerobot_http_exception_handler)  # type: ignore

    if not status.debug:
        app.add_exception_handler(Exception, exception_handler)
