import typing
import importlib
import json

import httpx

from ...version import VERSION
from ...log import logger
from ...exceptions import NetworkServerError, NetworkClientError, NetworkInvalidContentError, NetworkError

if typing.TYPE_CHECKING:
    # For type checking and IDE support
    from .mirai import (
        get_bot_list, get_bot_profile, get_friend_list, get_group_list, get_friend_profile, get_group_member_profile,
        set_group_member_info, send_friend_message, send_group_message, send_temp_message,
        respond_new_friend_request_event, respond_bot_invited_join_group_request_event
    )


class Client(httpx.Client):
    @staticmethod
    def log_request(request: httpx.Request):
        logger.debug(f"Request to {request.method} {request.url}, content: {request.content.decode()}")

    @staticmethod
    def log_response(response: httpx.Response):
        response.read()

        json_decoded = False
        result = None
        content_or_json = response.text

        try:
            result = response.json()
            content_or_json = json.dumps(result)
            json_decoded = True
        except json.JSONDecodeError:
            pass

        logger.debug(f"Response from {response.request.method} {response.request.url}, content: {content_or_json}")

        if response.status_code >= 500:
            logger.error(f"Failed to request {response.request.url}, HTTP status code {response.status_code} returned")
            raise NetworkServerError()
        elif response.status_code >= 400:
            logger.error(f"Failed to request {response.request.url}, HTTP status code {response.status_code} returned")
            raise NetworkClientError()

        # Check if the content is valid json
        if not json_decoded:
            logger.error(f"Failed to request {response.request.url}, invalid content returned")
            raise NetworkInvalidContentError()

        if "code" in result and result["code"] != 0:
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


_dynamic_imports: dict[str, str] = {
    "get_bot_list": ".mirai",
    "get_bot_profile": ".mirai",
    "get_friend_list": ".mirai",
    "get_group_list": ".mirai",
    "get_friend_profile": ".mirai",
    "get_group_member_profile": ".mirai",
    "set_group_member_info": ".mirai",
    "send_friend_message": ".mirai",
    "send_group_message": ".mirai",
    "send_temp_message": ".mirai",
    "respond_new_friend_request_event": ".mirai",
    "respond_bot_invited_join_group_request_event": ".mirai"
}
client = Client()


def __getattr__(name) -> object:
    if name in _dynamic_imports:
        return getattr(importlib.import_module(_dynamic_imports[name], __package__), name)
    else:
        raise AttributeError(f"Module \"{__name__}\" has no attribute \"{name}\"")
