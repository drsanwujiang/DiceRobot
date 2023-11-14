from fastapi import FastAPI
from fastapi.responses import JSONResponse

from .log import logger
from .config import replies


class DiceRobotException(Exception):
    def __init__(self, reply: str) -> None:
        self.reply = reply


class NetworkClientException(DiceRobotException):
    def __init__(self) -> None:
        super().__init__(replies["dicerobot"]["network_client_error"])


class NetworkServerException(DiceRobotException):
    def __init__(self) -> None:
        super().__init__(replies["dicerobot"]["network_server_error"])


class NetworkInvalidContentException(DiceRobotException):
    def __init__(self) -> None:
        super().__init__(replies["dicerobot"]["network_invalid_content"])


class NetworkErrorException(DiceRobotException):
    def __init__(self) -> None:
        super().__init__(replies["dicerobot"]["network_error"])


class OrderInvalidException(DiceRobotException):
    def __init__(self) -> None:
        super().__init__(replies["dicerobot"]["order_invalid"])


class OrderException(DiceRobotException):
    pass


class HTTPException(Exception):
    def __init__(self, status_code: int, code: int, message: str) -> None:
        self.status_code = status_code
        self.code = code
        self.message = message


class TokenInvalidException(HTTPException):
    def __init__(
        self,
        status_code: int = 401,
        code: int = -1,
        message: str = "Invalid token"
    ) -> None:
        super().__init__(status_code, code, message)


class AuthenticationException(HTTPException):
    def __init__(
        self,
        status_code: int = 401,
        code: int = -2,
        message: str = "Authentication failed"
    ) -> None:
        super().__init__(status_code, code, message)


class MessageInvalidException(HTTPException):
    def __init__(
        self,
        status_code: int = 400,
        code: int = -3,
        message: str = "Invalid message"
    ) -> None:
        super().__init__(status_code, code, message)


class ParametersInvalidException(HTTPException):
    def __init__(
            self,
            status_code: int = 400,
            code: int = -4,
            message: str = "Invalid parameters"
    ) -> None:
        super().__init__(status_code, code, message)


def init_handlers(app: FastAPI) -> None:
    logger.info("Initializing exception handlers")

    @app.exception_handler(HTTPException)
    async def http_exception_handler(_, e: HTTPException):
        return JSONResponse(
            status_code=e.status_code,
            content={"code": e.code, "message": e.message}
        )

    logger.info("Exception handlers initialized")
