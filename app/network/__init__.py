import json

import httpx

from ..version import VERSION
from ..log import logger
from ..exceptions import NetworkServerError, NetworkClientError, NetworkInvalidContentError, NetworkError


class Client(httpx.Client):
    @staticmethod
    def log_request(request: httpx.Request):
        logger.debug(f"Request: {request.method} {request.url} {request.content.decode()}")

    @staticmethod
    def log_response(response: httpx.Response):
        response.read()

        try:
            result = response.json()
        except json.JSONDecodeError:
            logger.error(f"Failed to request {response.request.url}, invalid content returned")
            raise NetworkInvalidContentError()

        logger.debug(f"Response: {response.request.method} {response.request.url}, content: {result}")

        if response.status_code >= 500:
            logger.error(f"Failed to request {response.request.url}, HTTP status code {response.status_code} returned")
            raise NetworkServerError()
        elif response.status_code >= 400:
            logger.error(f"Failed to request {response.request.url}, HTTP status code {response.status_code} returned")
            raise NetworkClientError()

        if "code" in result and result["code"] != 0 and result["code"] != 200:
            error_code = result["code"]
            error_message = result["msg"] if "msg" in result else result["message"] if "message" in result else None

            if error_message:
                logger.error(f"API returned unexpected code {error_code}, error message: {error_message}")

    _defaults = {
        "headers": {
            "Content-Type": "application/json",
            "Accept": "application/json",
            "User-Agent": f"DiceRobot/{VERSION}"
        },
        "timeout": 30,  # For some file uploading
        "event_hooks": {
            "request": [log_request],
            "response": [log_response]
        }
    }

    def __init__(self, *args, **kwargs):
        kwargs = Client._defaults | kwargs
        super().__init__(*args, **kwargs)

    def request(self, *args, **kwargs) -> httpx.Response:
        try:
            return super().request(*args, **kwargs)
        except httpx.HTTPError as e:
            logger.error(f"Failed to request {e.request.url}, {e.__class__.__name__} occurred")
            raise NetworkError()


client = Client()
