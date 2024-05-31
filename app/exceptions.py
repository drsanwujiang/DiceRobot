from .config import replies


class DiceRobotException(Exception):
    def __init__(self, reply: str) -> None:
        self.reply = reply


class NetworkClientError(DiceRobotException):
    def __init__(self) -> None:
        super().__init__(replies.get_reply(reply_group="dicerobot", reply_key="network_client_error"))


class NetworkServerError(DiceRobotException):
    def __init__(self) -> None:
        super().__init__(replies.get_reply(reply_group="dicerobot", reply_key="network_server_error"))


class NetworkInvalidContentError(DiceRobotException):
    def __init__(self) -> None:
        super().__init__(replies.get_reply(reply_group="dicerobot", reply_key="network_invalid_content"))


class NetworkError(DiceRobotException):
    def __init__(self) -> None:
        super().__init__(replies.get_reply(reply_group="dicerobot", reply_key="network_error"))


class OrderInvalidError(DiceRobotException):
    def __init__(self) -> None:
        super().__init__(replies.get_reply(reply_group="dicerobot", reply_key="order_invalid"))


class OrderError(DiceRobotException):
    pass


class DiceRobotHTTPException(Exception):
    def __init__(self, status_code: int, code: int, message: str) -> None:
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
