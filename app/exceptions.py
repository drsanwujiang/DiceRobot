__all__ = [
    "DiceRobotRuntimeException",
    "NetworkClientError",
    "NetworkServerError",
    "NetworkInvalidContentError",
    "NetworkError",
    "OrderInvalidError",
    "OrderSuspiciousError",
    "OrderRepetitionExceededError",
    "OrderError",
    "DiceRobotHTTPException",
    "TokenInvalidError",
    "SignatureInvalidError",
    "MessageInvalidError",
    "ParametersInvalidError",
    "ResourceNotFoundError",
    "BadRequestError"
]


class DiceRobotRuntimeException(Exception):
    def __init__(self, message: str = None, key: str = None) -> None:
        super().__init__(message)

        self.message = message
        self.key = key


class NetworkClientError(DiceRobotRuntimeException):
    def __init__(self) -> None:
        super().__init__(key="network_client_error")


class NetworkServerError(DiceRobotRuntimeException):
    def __init__(self) -> None:
        super().__init__(key="network_server_error")


class NetworkInvalidContentError(DiceRobotRuntimeException):
    def __init__(self) -> None:
        super().__init__(key="network_invalid_content")


class NetworkError(DiceRobotRuntimeException):
    def __init__(self) -> None:
        super().__init__(key="network_error")


class OrderInvalidError(DiceRobotRuntimeException):
    def __init__(self) -> None:
        super().__init__(key="order_invalid")


class OrderSuspiciousError(DiceRobotRuntimeException):
    def __init__(self) -> None:
        super().__init__(key="order_suspicious")


class OrderRepetitionExceededError(DiceRobotRuntimeException):
    def __init__(self) -> None:
        super().__init__(key="order_repetition_exceeded")


class OrderError(DiceRobotRuntimeException):
    ...


class DiceRobotHTTPException(Exception):
    def __init__(self, status_code: int, code: int, message: str) -> None:
        super().__init__(message)

        self.status_code = status_code
        self.code = code
        self.message = message


class TokenInvalidError(DiceRobotHTTPException):
    def __init__(
        self,
        status_code: int = 401,
        code: int = -1,
        message: str = "Invalid token"
    ) -> None:
        super().__init__(status_code, code, message)


class SignatureInvalidError(DiceRobotHTTPException):
    def __init__(
        self,
        status_code: int = 401,
        code: int = -1,
        message: str = "Invalid signature"
    ) -> None:
        super().__init__(status_code, code, message)


class MessageInvalidError(DiceRobotHTTPException):
    def __init__(
        self,
        status_code: int = 400,
        code: int = -2,
        message: str = "Invalid message"
    ) -> None:
        super().__init__(status_code, code, message)


class ParametersInvalidError(DiceRobotHTTPException):
    def __init__(
        self,
        status_code: int = 400,
        code: int = -3,
        message: str = "Invalid parameters"
    ) -> None:
        super().__init__(status_code, code, message)


class ResourceNotFoundError(DiceRobotHTTPException):
    def __init__(
        self,
        status_code: int = 404,
        code: int = -4,
        message: str = "Resource not found"
    ) -> None:
        super().__init__(status_code, code, message)


class BadRequestError(DiceRobotHTTPException):
    def __init__(
        self,
        status_code: int = 400,
        code: int = -5,
        message: str = "Bad request"
    ) -> None:
        super().__init__(status_code, code, message)
