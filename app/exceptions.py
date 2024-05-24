from fastapi import FastAPI
from fastapi.responses import JSONResponse

from .log import logger
from .config import replies


class DiceRobotException(Exception):
    def __init__(self, reply: str) -> None:
        self.reply = reply


class NetworkClientError(DiceRobotException):
    def __init__(self) -> None:
        super().__init__(replies["dicerobot"]["network_client_error"])


class NetworkServerError(DiceRobotException):
    def __init__(self) -> None:
        super().__init__(replies["dicerobot"]["network_server_error"])


class NetworkInvalidContentError(DiceRobotException):
    def __init__(self) -> None:
        super().__init__(replies["dicerobot"]["network_invalid_content"])


class NetworkError(DiceRobotException):
    def __init__(self) -> None:
        super().__init__(replies["dicerobot"]["network_error"])


class OrderInvalidError(DiceRobotException):
    def __init__(self) -> None:
        super().__init__(replies["dicerobot"]["order_invalid"])


class OrderException(DiceRobotException):
    pass


class HTTPError(Exception):
    def __init__(self, status_code: int, code: int, message: str) -> None:
        self.status_code = status_code
        self.code = code
        self.message = message


class TokenInvalidError(HTTPError):
    def __init__(
        self,
        status_code: int = 401,
        code: int = -1,
        message: str = "Invalid token"
    ) -> None:
        super().__init__(status_code, code, message)


class AuthenticationError(HTTPError):
    def __init__(
        self,
        status_code: int = 401,
        code: int = -2,
        message: str = "Authentication failed"
    ) -> None:
        super().__init__(status_code, code, message)


class MessageInvalidError(HTTPError):
    def __init__(
        self,
        status_code: int = 400,
        code: int = -3,
        message: str = "Invalid message"
    ) -> None:
        super().__init__(status_code, code, message)


class ParametersInvalidError(HTTPError):
    def __init__(
            self,
            status_code: int = 400,
            code: int = -4,
            message: str = "Invalid parameters"
    ) -> None:
        super().__init__(status_code, code, message)


def init_handlers(app: FastAPI) -> None:
    @app.exception_handler(HTTPError)
    async def http_exception_handler(_, e: HTTPError):
        return JSONResponse(
            status_code=e.status_code,
            content={"code": e.code, "message": e.message}
        )
