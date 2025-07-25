import json

from loguru import logger
from httpx import AsyncClient, Request, Response, HTTPError

from ..version import VERSION
from ..exceptions import NetworkServerError, NetworkClientError, NetworkInvalidContentError, NetworkError


class Client(AsyncClient):
    @staticmethod
    async def log_request(request: Request) -> None:
        await request.aread()

        logger.debug(f"Request: {request.method} {request.url}, content: {request.content.decode()}")

    @staticmethod
    async def log_response(response: Response) -> None:
        # Check JSON content
        if "application/json" in response.headers.get("content-type", ""):
            await response.aread()

            try:
                result = response.json()
            except json.JSONDecodeError:
                logger.error(f"Failed to request {response.request.url}, invalid content returned")
                raise NetworkInvalidContentError

            logger.debug(f"Response: {response.request.method} {response.request.url}, content: {result}")

            if ("code" in result and result["code"] != 0 and result["code"] != 200) or \
                    ("retcode" in result and result["retcode"] != 0):
                error_code = result["code"]
                error_message = result["msg"] if "msg" in result else result["message"] if "message" in result else None

                if error_message:
                    logger.error(f"API returned unexpected code {error_code}, error message: {error_message}")

        # Check HTTP status code
        if response.status_code >= 500:
            logger.error(f"Failed to request {response.request.url}, HTTP status code {response.status_code} returned")
            raise NetworkServerError
        elif response.status_code >= 400:
            logger.error(f"Failed to request {response.request.url}, HTTP status code {response.status_code} returned")
            raise NetworkClientError

    _defaults = {
        "headers": {
            "Accept": "application/json",
            "User-Agent": f"DiceRobot/{VERSION}"
        },
        "timeout": 30,  # For some file uploading
        "event_hooks": {
            "request": [log_request],
            "response": [log_response]
        }
    }

    def __init__(self, *args, **kwargs) -> None:
        kwargs = Client._defaults | kwargs
        super().__init__(*args, **kwargs)

    async def request(self, *args, **kwargs) -> Response:
        try:
            return await super().request(*args, **kwargs)
        except HTTPError as e:
            logger.error(f"Failed to request {e.request.url}, \"{e.__class__.__name__}\" occurred")
            raise NetworkError


client = Client()
